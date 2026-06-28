<?php

namespace App\Console\Commands\legacy;

use App\Models\Enums\BudgetType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-references the booking table against the old (haushaltstitel) and new
 * (budget_item) title tables, so a migrated production database can be checked
 * before the legacy tables are frozen/removed.
 *
 * Because the conversion preserves leaf ids (haushaltstitel.id == budget_item.id)
 * and the booking rows themselves never move, the per-title booking sum is the
 * same number whichever side it is joined to. What this command verifies is that
 * every booked title still resolves to a *bookable* budget_item (a leaf, not a
 * group and not a mount), and that the old/new identities line up for a human to
 * eyeball. The printed fingerprint is an integrity tripwire on the booking table
 * itself: capture it before the upgrade and after — if it changes, bookings were
 * lost or altered and you should roll back from backup.
 */
class VerifyBookingMigration extends Command
{
    protected $signature = 'legacy:verify-booking-migration
                            {--json= : Write the full structured report to this path}
                            {--all-titles : Also include titles that have no bookings}';

    protected $description = 'Cross-reference bookings against old (haushaltstitel) and new (budget_item) titles to verify the migration';

    /** Blocker statuses make the command exit non-zero (deploy gate). */
    private const BLOCKERS = ['MISSING_NEW', 'GROUP_NOT_BOOKABLE', 'MOUNT_NOT_BOOKABLE', 'ORPHAN_BOOKING'];

    public function handle(): int
    {
        if (! Schema::hasTable('budget_item')) {
            $this->error('budget_item table is missing — run the conversion first.');

            return self::FAILURE;
        }

        $legacyPresent = Schema::hasTable('haushaltstitel');
        if (! $legacyPresent) {
            $this->warn('⚠️  haushaltstitel is gone — old-side ground truth is no longer available, '
                .'only new-side bookability can be checked.');
        }

        // booking sums per titel_id (canceled is unused, so every row counts)
        $bookingAgg = DB::table('booking')
            ->select('titel_id', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(value), 0) as sum'))
            ->groupBy('titel_id')
            ->get()
            ->keyBy('titel_id');

        $old = $legacyPresent
            ? DB::table('haushaltstitel as t')
                ->leftJoin('haushaltsgruppen as g', 't.hhpgruppen_id', '=', 'g.id')
                ->select('t.id', 't.titel_nr', 't.titel_name', 'g.type', 'g.gruppen_name')
                ->get()->keyBy('id')
            : collect();

        $new = DB::table('budget_item')
            ->select('id', 'short_name', 'name', 'is_group', 'referenced_plan_id', 'budget_type')
            ->get()->keyBy('id');

        // which titel_ids to report: every booked title, plus all titles if asked
        $titelIds = $bookingAgg->keys();
        if ($this->option('all-titles')) {
            $titelIds = $titelIds->merge($old->keys())->merge($new->keys());
        }
        $titelIds = $titelIds->filter(fn ($id) => $id !== null)->unique()->sort()->values();

        $rows = [];
        $report = [];
        $statusCounts = [];
        $fingerprintLines = [];

        // orphan bookings: titel_id null
        if ($bookingAgg->has(null) || $bookingAgg->has('')) {
            $orphan = $bookingAgg->get(null) ?? $bookingAgg->get('');
            $rows[] = ['(null)', '—', '—', $orphan->cnt, $this->money($orphan->sum), 'ORPHAN_BOOKING'];
            $statusCounts['ORPHAN_BOOKING'] = ($statusCounts['ORPHAN_BOOKING'] ?? 0) + 1;
        }

        foreach ($titelIds as $id) {
            $agg = $bookingAgg->get($id);
            $cnt = (int) ($agg->cnt ?? 0);
            $sum = (float) ($agg->sum ?? 0);
            $oldRow = $old->get($id);
            $newRow = $new->get($id);

            $status = $this->statusFor($oldRow, $newRow);

            $oldLabel = $oldRow
                ? trim(($oldRow->titel_nr ? $oldRow->titel_nr.' ' : '').$oldRow->titel_name)
                : '—';
            $newLabel = $newRow
                ? trim(($newRow->short_name ? $newRow->short_name.' ' : '').$newRow->name)
                : '—';

            $rows[] = [$id, $oldLabel, $newLabel, $cnt, $this->money($sum), $status];
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if ($cnt > 0) {
                $fingerprintLines[] = $id.'|'.number_format($sum, 2, '.', '');
            }

            $report[] = [
                'titel_id' => $id,
                'bookings' => $cnt,
                'sum' => round($sum, 2),
                'old' => $oldRow ? ['nr' => $oldRow->titel_nr, 'name' => $oldRow->titel_name, 'type' => $oldRow->type] : null,
                'new' => $newRow ? ['short_name' => $newRow->short_name, 'name' => $newRow->name, 'kind' => $this->kind($newRow)] : null,
                'status' => $status,
            ];
        }

        $this->table(['titel_id', 'old (haushaltstitel)', 'new (budget_item)', '#', 'sum', 'status'], $rows);

        $this->renderSummary($statusCounts, $old, $new, $fingerprintLines);

        if ($path = $this->option('json')) {
            file_put_contents($path, json_encode([
                'generated_at' => now()->toIso8601String(),
                'fingerprint' => $this->fingerprint($fingerprintLines),
                'grand_total_bookings' => (int) DB::table('booking')->count(),
                'grand_total_sum' => round((float) DB::table('booking')->sum('value'), 2),
                'status_counts' => $statusCounts,
                'titles' => $report,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("📄 Report written to {$path}");
        }

        $blockers = collect($statusCounts)->only(self::BLOCKERS)->sum();
        if ($blockers > 0) {
            $this->error("\n❌ {$blockers} title(s) in a blocking state — do NOT consider the migration successful.");

            return self::FAILURE;
        }

        $this->info("\n✅ Every booked title resolves to a bookable budget_item.");

        return self::SUCCESS;
    }

    /**
     * Worst-severity status for one title. Blockers first (a booking would hang
     * off a non-bookable or missing item), then human-eyeball warnings.
     */
    private function statusFor(?object $oldRow, ?object $newRow): string
    {
        if ($newRow === null) {
            return 'MISSING_NEW';
        }
        if ($newRow->referenced_plan_id !== null) {
            return 'MOUNT_NOT_BOOKABLE';
        }
        if ((bool) $newRow->is_group) {
            return 'GROUP_NOT_BOOKABLE';
        }
        if ($oldRow === null) {
            return 'MISSING_OLD'; // new-only title (expected only if the new UI added titles)
        }
        if ($oldRow->type !== null && $this->expectedType($oldRow->type) !== (int) $newRow->budget_type) {
            return 'TYPE_DIFF';
        }
        if ($this->norm($oldRow->titel_name) !== $this->norm($newRow->name)) {
            return 'NAME_DIFF';
        }

        return 'OK';
    }

    /** Legacy group type (0 = income, else expense) → new BudgetType value. */
    private function expectedType(int $legacyType): int
    {
        return ($legacyType === 0 ? BudgetType::INCOME : BudgetType::EXPENSE)->value;
    }

    private function kind(object $newRow): string
    {
        if ($newRow->referenced_plan_id !== null) {
            return 'mount';
        }

        return $newRow->is_group ? 'group' : 'budget';
    }

    private function renderSummary(array $statusCounts, $old, $new, array $fingerprintLines): void
    {
        $this->newLine();
        $this->line('<comment>Status breakdown:</comment>');
        foreach ($statusCounts as $status => $count) {
            $marker = in_array($status, self::BLOCKERS, true) ? '❌' : ($status === 'OK' ? '✓' : '⚠️ ');
            $this->line("  {$marker} {$status}: {$count}");
        }

        $bookableNew = $new->filter(fn ($r) => ! $r->is_group && $r->referenced_plan_id === null)->count();

        $this->newLine();
        $this->line('<comment>Coverage:</comment>');
        if ($old->isNotEmpty()) {
            $this->line("  legacy titles (haushaltstitel): {$old->count()}");
        }
        $this->line("  bookable budget_items:          {$bookableNew}");

        $this->newLine();
        $this->line('<comment>Booking-table integrity (compare before vs after the upgrade):</comment>');
        $this->line('  grand total bookings: '.DB::table('booking')->count());
        $this->line('  grand total sum:      '.$this->money((float) DB::table('booking')->sum('value')));
        $this->line('  fingerprint:          '.$this->fingerprint($fingerprintLines));
    }

    /** Stable hash over per-title booking sums; identical pre/post unless bookings changed. */
    private function fingerprint(array $lines): string
    {
        sort($lines);

        return sha1(implode("\n", $lines));
    }

    private function norm(?string $s): string
    {
        return trim((string) $s);
    }

    private function money(float $v): string
    {
        return number_format($v, 2, ',', '.').' €';
    }
}
