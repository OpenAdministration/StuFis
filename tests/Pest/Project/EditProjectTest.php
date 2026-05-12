<?php

namespace Tests\Pest\Project;

use App\Livewire\Project\EditProject;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use Cknow\Money\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->budgetPlan = LegacyBudgetPlan::create([
        'von' => now()->startOfYear(),
        'bis' => now()->endOfYear(),
        'state' => 'final',
    ]);
    $this->actingAs(user());
});

it('can render the create project page', function () {
    Livewire::test(EditProject::class)
        ->assertStatus(200)
        ->assertSet('isNew', true)
        ->assertCount('posts', 1);
});

it('can create a new project', function () {
    Storage::fake('projects');
    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    Livewire::test(EditProject::class)
        ->set('name', 'Test Project')
        ->set('responsible', 'test@example.com')
        ->set('org', 'Test Org')
        ->set('beschreibung', 'This is a description that is long enough for validation.')
        ->set('dateRange', ['start' => now()->format('Y-m-d'), 'end' => now()->addDays(5)->format('Y-m-d')])
        ->set('hhp_id', $this->budgetPlan->id)

        ->set('posts.0.name', 'First Post')
        ->set('posts.0.einnahmen', Money::EUR(0))
        ->set('posts.0.ausgaben', Money::EUR(10000)) // 100.00 EUR

        ->set('newAttachments', [$file])
        ->call('saveAs', 'draft');

    $project = Project::where('name', 'Test Project')->first();
    expect($project)->not->toBeNull()
        ->and($project->state->getValue())->toBe('draft')
        ->and($project->posts)->toHaveCount(1)
        ->and($project->attachments)->toHaveCount(1);
});

it('can load an existing project for editing', function () {
    $project = Project::factory()->by(user())->create([
        'name' => 'Existing Project',
    ]);
    $project->posts()->create([
        'name' => 'Existing Post',
        'einnahmen' => Money::EUR(0),
        'ausgaben' => Money::EUR(5000),
        'bemerkung' => 'This is a description that is long enough for validation.',
    ]);

    Livewire::test(EditProject::class, ['project_id' => $project->id])
        ->assertSet('name', 'Existing Project')
        ->assertCount('posts', 1)
        ->assertSet('posts.0.name', 'Existing Post');
});

it('can add and remove posts', function () {
    Livewire::test(EditProject::class)
        ->assertCount('posts', 1)
        ->call('addEmptyPost')
        ->assertCount('posts', 2)
        ->call('removePost', 1)
        ->assertCount('posts', 1);
});

it('prevents saving if version has changed (optimistic locking)', function () {
    $project = Project::factory()->by(user())->create(['version' => 1]);

    $component = Livewire::test(EditProject::class, ['project_id' => $project->id]);

    // Simulate another user updating the project in the background
    $project->increment('version');

    $component->set('name', 'Updated Name')
        ->call('saveAs', 'draft')
        ->assertHasErrors(['save']);

    expect($project->refresh()->name)->not->toBe('Updated Name');
});

it('validates required fields based on state rules', function () {
    Livewire::test(EditProject::class)
        ->call('saveAs', 'applied')
        ->errors();
    /*->assertHasErrors([
        'name',
        'responsible',
        'org',
        'date_start',
        'date_end',
        'beschreibung'
    ]);*/
});
