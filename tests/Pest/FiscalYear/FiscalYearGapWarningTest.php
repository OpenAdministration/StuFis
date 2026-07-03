<?php

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

it('warns when the entered range leaves a gap before an existing fiscal year', function (): void {
    $this->actingAs(budgetManager());

    // an existing year Apr 24 – Mär 25; a new year starting Mai 25 leaves a gap in April 25
    FiscalYear::factory()->create(['start_date' => '2024-04-01', 'end_date' => '2025-03-31']);

    Livewire::test('pages::fiscal-year.edit-fiscal-year')
        ->set('start_date', '2025-05-01')
        ->set('end_date', '2026-04-30')
        ->assertSee(__('budget-plan.fiscal-year.gap-warning.heading'))
        ->assertSee('01.04.2025')
        ->assertSee('30.04.2025');
});

it('does not warn when the entered range abuts the neighbour exactly', function (): void {
    $this->actingAs(budgetManager());

    FiscalYear::factory()->create(['start_date' => '2024-04-01', 'end_date' => '2025-03-31']);

    Livewire::test('pages::fiscal-year.edit-fiscal-year')
        ->set('start_date', '2025-04-01')
        ->set('end_date', '2026-03-31')
        ->assertDontSee(__('budget-plan.fiscal-year.gap-warning.heading'));
});

it('ignores the year being edited when checking for gaps', function (): void {
    $this->actingAs(budgetManager());

    $before = FiscalYear::factory()->create(['start_date' => '2023-04-01', 'end_date' => '2024-03-31']);
    $editing = FiscalYear::factory()->create(['start_date' => '2024-04-01', 'end_date' => '2025-03-31']);

    Livewire::test('pages::fiscal-year.edit-fiscal-year', ['year_id' => $editing->id])
        ->assertDontSee(__('budget-plan.fiscal-year.gap-warning.heading'));
});
