<?php

namespace Tests\Pest\Project;

use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use Livewire\Livewire;

beforeEach(function (): void {
    // relatedBudgetPlan()->label() / the budget-plan link need a covering plan.
    LegacyBudgetPlan::create([
        'von' => now()->startOfYear(),
        'bis' => now()->endOfYear(),
        'state' => 'final',
    ]);
    $this->actingAs(user());
});

it('shows a backlink to the source project on a copied project', function (): void {
    $source = Project::factory()->by(user())->create();
    $copy = Project::factory()->by(user())->create([
        'source_id' => $source->id,
        'source_kind' => 'copy',
    ]);

    Livewire::test('pages::project.show-project', ['project_id' => $copy->id])
        ->assertOk()
        ->assertSee('Kopiert aus')
        ->assertSee("P#{$source->id}");
});

it('shows forward references to derived leftover projects on the source', function (): void {
    $source = Project::factory()->by(user())->create();
    $leftover = Project::factory()->by(user())->create(['source_id' => $source->id, 'source_kind' => 'leftovers']);

    Livewire::test('pages::project.show-project', ['project_id' => $source->id])
        ->assertOk()
        ->assertSee('Restmittel übertragen nach')
        ->assertSee("P#{$leftover->id}");
});

it('links the budget plan to the legacy plan view', function (): void {
    $project = Project::factory()->by(user())->create();
    $plan = $project->relatedBudgetPlan();

    Livewire::test('pages::project.show-project', ['project_id' => $project->id])->assertOk()->assertSeeHtml(route('legacy.hhp.view', $plan->id));
});

/**
 * The attachment cards drive the shared <x-file-preview-modal>: a previewable file
 * dispatches `file-preview` on click instead of navigating, while a format no browser
 * can render in a frame keeps the plain link. Both keep their download action.
 */
it('wires previewable attachments to the preview modal and leaves office files alone', function (): void {
    $project = Project::factory()->by(user())->create();
    $pdf = $project->attachments()->create([
        'path' => 'projects/'.$project->id.'/plan.pdf',
        'name' => 'plan.pdf', 'mime_type' => 'application/pdf', 'size' => 2048,
    ]);
    $docx = $project->attachments()->create([
        'path' => 'projects/'.$project->id.'/notes.docx',
        'name' => 'notes.docx', 'size' => 4096,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);

    $component = Livewire::test('pages::project.show-project', ['project_id' => $project->id])
        ->assertOk()
        // one shared modal for the whole page, not one per attachment
        ->assertSeeHtml('x-on:file-preview.window')
        // the pdf opens it...
        ->assertSeeHtml("kind: 'pdf'")
        // ...and both files can still be downloaded
        ->assertSeeHtml(route('project.attachment.download', [$pdf->id, $pdf->name]))
        ->assertSeeHtml(route('project.attachment.download', [$docx->id, $docx->name]));

    // The docx card has no preview wiring at all -- no `word` kind is ever dispatched.
    expect($component->html())->not->toContain("kind: 'word'");
});
