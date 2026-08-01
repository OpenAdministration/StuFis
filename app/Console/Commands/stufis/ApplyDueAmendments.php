<?php

namespace App\Console\Commands\stufis;

use App\Models\BudgetPlan;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\Support\Budget\AmendmentConflictException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Throwable;

/**
 * Scheduled effectiveness for Nachtragshaushaltspläne (amendments, OP#581): an approved amendment
 * with an `effective_date` in the past should go live on its own, without someone manually
 * clicking "aktivieren" on the day. Runs daily (see routes/console.php).
 *
 * Every due amendment is transitioned independently, so one amendment's conflict (e.g. a stale
 * item, or its parent plan no longer being Active) doesn't block the others. Failures are logged
 * and reported on stderr; the command exits non-zero when any amendment failed, so the run stays
 * visible and the schedule's failure hooks can act on it — the run is safely re-triggerable, since
 * only successfully-applied amendments leave Approved.
 */
class ApplyDueAmendments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stufis:apply-due-amendments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate approved Nachtragshaushaltspläne (amendments) whose effective_date has arrived';

    public function handle(): int
    {
        $due = BudgetPlan::query()
            ->whereNotNull('parent_plan_id')
            ->where('state', Approved::$name)
            ->whereNotNull('effective_date')
            ->whereDate('effective_date', '<=', today())
            ->get()
            ->filter(fn (BudgetPlan $amendment): bool => $amendment->parentPlan?->state instanceof Active);

        if ($due->isEmpty()) {
            $this->info('No due amendments to activate.');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($due as $amendment) {
            try {
                $amendment->state->transitionTo(Active::class);
                $this->info("Activated amendment #{$amendment->id} ({$amendment->label()}).");
            } catch (AmendmentConflictException|CouldNotPerformTransition $e) {
                $failed++;
                $this->error("Amendment #{$amendment->id} could not be activated: {$e->getMessage()}");
                Log::warning('stufis:apply-due-amendments failed for amendment', [
                    'amendment_id' => $amendment->id,
                    'message' => $e->getMessage(),
                ]);
            } catch (Throwable $e) {
                $failed++;
                $this->error("Amendment #{$amendment->id} could not be activated: {$e->getMessage()}");
                Log::error('stufis:apply-due-amendments unexpected failure for amendment', [
                    'amendment_id' => $amendment->id,
                    'exception' => $e,
                ]);
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
