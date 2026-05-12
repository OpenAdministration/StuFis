<?php

use App\Models\PtfProject\FormDefinition;
use App\Models\PtfProject\FormField;
use App\Models\PtfProject\FormFieldOption;
use App\Models\PtfProject\FormFieldValidation;

use function Pest\Laravel\assertModelExists;

test('form definition factory', function (): void {
    $def = FormDefinition::factory()->has(
        FormField::factory(5)->has(
            FormFieldOption::factory()->count(5)
        )->has(
            FormFieldValidation::factory()->count(5)
        )
    )->create();

    assertModelExists($def);
    expect($def->formFields->count())->toBe(5);

})->todo();
