<?php

use App\Models\FormDefinition;

test('application factory', function (): void {
    $p_form = FormDefinition::factory()->forProject()->create();
    $a_form = FormDefinition::factory()->forApplication()->create();

})->todo();
