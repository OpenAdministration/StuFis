<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retires `konto_bank`, whose four columns (id, blz, name, url) were a hand-maintained
 * subset of what `fints_institutes` now syncs from the public bank list. Bank accesses
 * reference the Bankleitzahl directly from here on, so a bank's name and FinTS endpoint
 * have exactly one source.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Instances upgraded from v3 carry the legacy tables in whatever collation their
        // server defaulted to back then (utf8mb4_general_ci), while Laravel creates new ones
        // in the collation from config/database.php (utf8mb4_unicode_ci). InnoDB refuses a
        // foreign key whose two columns disagree on collation, so this one is pinned to
        // whatever the column it points at actually uses instead of inheriting the table's.
        $collation = $this->columnCollation('fints_institutes', 'blz');

        // Guarded because MariaDB cannot roll back DDL: if a later step here fails, the
        // migration is not recorded and has to survive being run again.
        if (! Schema::hasColumn('konto_credentials', 'blz')) {
            Schema::table('konto_credentials', function (Blueprint $table) use ($collation) {
                $table->char('blz', 8)->collation($collation)->nullable()->after('id');
            });
        }

        // Carry the existing accesses over before konto_bank goes away. konto_bank.blz is an
        // INT, so 8-digit-pad it into the char column the institute list uses. Skipped when
        // a previous run already got past the point where bank_id is dropped - the values
        // are in blz by then, and the column this reads is gone.
        if (Schema::hasColumn('konto_credentials', 'bank_id')) {
            foreach (DB::table('konto_bank')->pluck('blz', 'id') as $bankId => $blz) {
                DB::table('konto_credentials')
                    ->where('bank_id', $bankId)
                    ->update(['blz' => str_pad((string) $blz, 8, '0', STR_PAD_LEFT)]);
            }
        }

        $orphaned = DB::table('konto_credentials')->whereNull('blz')->count();
        if ($orphaned > 0) {
            throw new RuntimeException(
                "$orphaned Bankzugänge verweisen auf keine Bank in konto_bank - bitte vor der Migration klären."
            );
        }

        // The foreign key needs every referenced BLZ to exist, and the sync cannot have run
        // yet - the table it fills is created by the migration right before this one. So seed
        // the institutes in use from the konto_bank rows being retired: same name, same URL,
        // so existing accesses keep working exactly as before until the first real sync
        // replaces these rows with authoritative data.
        $seededAt = DB::table('konto_credentials')->exists() ? Date::now() : null;

        // konto_bank is dropped in the very last statement, so it is still here on a re-run.
        foreach (Schema::hasTable('konto_bank') ? DB::table('konto_bank')->get() : [] as $bank) {
            $blz = str_pad((string) $bank->blz, 8, '0', STR_PAD_LEFT);

            $stillInUse = DB::table('konto_credentials')->where('blz', $blz)->exists();
            $alreadySynced = DB::table('fints_institutes')->where('blz', $blz)->exists();

            if (! $stillInUse || $alreadySynced) {
                continue;
            }

            DB::table('fints_institutes')->insert([
                'blz' => $blz,
                'name' => $bank->name,
                'pin_tan_address' => $bank->url,
                'synced_at' => $seededAt,
                'created_at' => $seededAt,
                'updated_at' => $seededAt,
            ]);
        }

        // The legacy migration hardcoded the name "dev__konto_credentials_ibfk_2" whatever the
        // table prefix, but a database restored from an older dump may carry a different one -
        // or several: a schema that has been dumped and restored across changes can accumulate
        // more than one foreign key on the same column. They share an index, so dropping only
        // one leaves that index alive and the column cannot be dropped (errno 1553).
        $foreignKeys = $this->foreignKeysOn('konto_credentials', 'bank_id');

        Schema::table('konto_credentials', function (Blueprint $table) use ($collation, $foreignKeys) {
            foreach ($foreignKeys as $foreignKey) {
                $table->dropForeign($foreignKey);
            }
            if (Schema::hasColumn('konto_credentials', 'bank_id')) {
                $table->dropColumn('bank_id');
            }
            // Also re-stated on an instance where the column already existed: a run that got
            // as far as the foreign key before failing left it in the legacy collation.
            $table->char('blz', 8)->collation($collation)->nullable(false)->change();
            $table->foreign('blz')->references('blz')->on('fints_institutes');
        });

        Schema::dropIfExists('konto_bank');
    }

    /**
     * Collation of the given column, or null when it is not a string column.
     */
    private function columnCollation(string $table, string $column): ?string
    {
        $row = DB::selectOne(
            'SELECT COLLATION_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::connection()->getTablePrefix().$table, $column],
        );

        return $row->COLLATION_NAME ?? null;
    }

    /**
     * Names of every foreign key on the given column.
     *
     * @return list<string>
     */
    private function foreignKeysOn(string $table, string $column): array
    {
        // Raw, because the query builder would prefix "information_schema.…" as a table name.
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [DB::connection()->getTablePrefix().$table, $column],
        );

        return array_map(static fn (object $row): string => $row->CONSTRAINT_NAME, $rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('konto_bank', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('url', 256);
            $table->integer('blz');
            $table->string('name', 256);
        });

        // Rebuild one konto_bank row per BLZ still in use, from the synced list.
        $banks = DB::table('konto_credentials')
            ->distinct()
            ->pluck('blz')
            ->values()
            ->mapWithKeys(function (string $blz, int $index) {
                $institute = DB::table('fints_institutes')->where('blz', $blz)->first();

                DB::table('konto_bank')->insert([
                    'id' => $index + 1,
                    'blz' => (int) $blz,
                    'name' => $institute->name ?? "BLZ $blz",
                    'url' => $institute->pin_tan_address ?? '',
                ]);

                return [$blz => $index + 1];
            });

        Schema::table('konto_credentials', function (Blueprint $table) {
            $table->dropForeign(['blz']);
            $table->integer('bank_id')->nullable()->after('name');
        });

        foreach ($banks as $blz => $bankId) {
            DB::table('konto_credentials')->where('blz', $blz)->update(['bank_id' => $bankId]);
        }

        Schema::table('konto_credentials', function (Blueprint $table) {
            $table->integer('bank_id')->nullable(false)->change();
            $table->dropColumn('blz');
            $table->foreign(['bank_id'], 'dev__konto_credentials_ibfk_2')->references(['id'])->on('konto_bank');
        });
    }
};
