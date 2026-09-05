<?php

/**
 * Instanzen, die von v3 hochgezogen wurden, führen ihre Legacy-Tabellen in
 * utf8mb4_general_ci, während Laravel neue Tabellen in der Kollation aus
 * config/database.php anlegt. InnoDB verweigert einen Fremdschlüssel, dessen
 * beide Spalten sich in der Kollation unterscheiden (errno 150), siehe OP#650.
 */
it('has no foreign key spanning two collations', function (): void {
    $mismatched = DB::select(
        'SELECT k.TABLE_NAME, k.COLUMN_NAME, c.COLLATION_NAME AS child,
                k.REFERENCED_TABLE_NAME, p.COLLATION_NAME AS parent
           FROM information_schema.KEY_COLUMN_USAGE k
           JOIN information_schema.COLUMNS c
             ON c.TABLE_SCHEMA = k.TABLE_SCHEMA AND c.TABLE_NAME = k.TABLE_NAME
            AND c.COLUMN_NAME = k.COLUMN_NAME
           JOIN information_schema.COLUMNS p
             ON p.TABLE_SCHEMA = k.TABLE_SCHEMA AND p.TABLE_NAME = k.REFERENCED_TABLE_NAME
            AND p.COLUMN_NAME = k.REFERENCED_COLUMN_NAME
          WHERE k.TABLE_SCHEMA = DATABASE()
            AND k.REFERENCED_TABLE_NAME IS NOT NULL
            AND c.COLLATION_NAME IS NOT NULL
            AND c.COLLATION_NAME <> p.COLLATION_NAME'
    );

    $describe = fn ($row) => "$row->TABLE_NAME.$row->COLUMN_NAME ($row->child) -> ".
        "$row->REFERENCED_TABLE_NAME.$row->REFERENCED_COLUMN_NAME ($row->parent)";

    expect(array_map($describe, $mismatched))->toBe([]);
});

it('points bank accesses at the institute list', function (): void {
    expect(Schema::hasColumn('konto_credentials', 'blz'))->toBeTrue()
        ->and(Schema::hasTable('konto_bank'))->toBeFalse();

    $columns = collect(DB::select(
        "SELECT TABLE_NAME, COLLATION_NAME FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'blz'
            AND TABLE_NAME IN (?, ?)",
        [DB::getTablePrefix().'konto_credentials', DB::getTablePrefix().'fints_institutes'],
    ))->pluck('COLLATION_NAME', 'TABLE_NAME');

    expect($columns)->toHaveCount(2)
        ->and($columns->unique())->toHaveCount(1);
});
