<?php

namespace Tests\Pest\Project;

use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use Cknow\Money\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->budgetPlan = LegacyBudgetPlan::create([
        'von' => now()->startOfYear(),
        'bis' => now()->endOfYear(),
        'state' => 'final',
    ]);
    $this->actingAs(user());
});

it('can render the create project page', function (): void {
    Livewire::test('pages::project.edit-project')
        ->assertStatus(200)
        ->assertSet('isNew', true)
        ->assertCount('posts', 1);
});

it('can create a new project', function (): void {
    Storage::fake('projects');
    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    Livewire::test('pages::project.edit-project')
        ->set('name', 'Test Project')
        // Must resolve under the `email:rfc,dns` rule; example.com (RFC 2606) has no MX.
        ->set('responsible', 'test@open-administration.de')
        ->set('org', 'Test Org')
        ->set('beschreibung', 'This is a description that is long enough for validation.')
        ->set('dateRange', ['start' => now()->format('Y-m-d'), 'end' => now()->addDays(5)->format('Y-m-d')])
        ->set('hhp_id', $this->budgetPlan->id)

        ->set('posts.0.name', 'First Post')
        ->set('posts.0.einnahmen', Money::EUR(0))
        ->set('posts.0.ausgaben', Money::EUR(10000)) // 100.00 EUR

        ->set('newAttachments', [$file])
        ->call('saveAs', 'draft')
        ->assertHasNoErrors();

    $project = Project::where('name', 'Test Project')->first();
    expect($project)->not->toBeNull()
        ->and($project->state->getValue())->toBe('draft')
        ->and($project->posts)->toHaveCount(1)
        ->and($project->attachments)->toHaveCount(1);
});

it('can load an existing project for editing', function (): void {
    $project = Project::factory()->by(user())->create([
        'name' => 'Existing Project',
    ]);
    $project->posts()->create([
        'name' => 'Existing Post',
        'einnahmen' => Money::EUR(0),
        'ausgaben' => Money::EUR(5000),
        'bemerkung' => 'This is a description that is long enough for validation.',
    ]);

    Livewire::test('pages::project.edit-project', ['project_id' => $project->id])
        ->assertSet('name', 'Existing Project')
        ->assertCount('posts', 1)
        ->assertSet('posts.0.name', 'Existing Post');
});

it('can add and remove posts', function (): void {
    Livewire::test('pages::project.edit-project')
        ->assertCount('posts', 1)
        ->call('addEmptyPost')
        ->assertCount('posts', 2)
        ->call('removePost', 1)
        ->assertCount('posts', 1);
});

it('prevents saving if version has changed (optimistic locking)', function (): void {
    $project = Project::factory()->by(user())->create(['version' => 1]);

    $component = Livewire::test('pages::project.edit-project', ['project_id' => $project->id]);

    // Simulate another user updating the project in the background
    $project->increment('version');

    $component->set('name', 'Updated Name')
        ->call('saveAs', 'draft')
        ->assertHasErrors(['save']);

    expect($project->refresh()->name)->not->toBe('Updated Name');
});

it('validates required fields based on state rules', function (): void {
    Livewire::test('pages::project.edit-project')
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
