<?php

use App\Models\LegalBasis;

use function Pest\Laravel\assertModelExists;

test('legal basis', function (): void {
    $legal = LegalBasis::factory()->create();
    assertModelExists($legal);
})->todo();
