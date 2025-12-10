<?php

use App\Models\PtfProject\LegalBasis;
use function Pest\Laravel\assertModelExists;

test('legal basis', function (): void {
    $legal = LegalBasis::factory()->create();
    assertModelExists($legal);
})->todo();
