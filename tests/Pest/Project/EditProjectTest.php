<?php

namespace Tests\Pest\Project;

use App\Models\Legacy\ExpenseReceiptPost;
use App\Models\Legacy\LegacyBudgetGroup;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use App\Models\LegalBasis;
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

    // Regression guard for the production "Unable to retrieve the file_size for
    // file at location: livewire-tmp/..." error: store() moves the temp file, so
    // the attachment metadata must be captured *before* the move. Assert the row
    // is fully populated and the stored file landed at the recorded path.
    $attachment = $project->attachments->first();
    expect($attachment->name)->toBe('document.pdf')
        ->and($attachment->size)->toBe(500 * 1024)
        ->and($attachment->mime_type)->toBe('application/pdf')
        ->and($attachment->path)->toStartWith("projects/{$project->id}/")
        ->and($attachment->path)->toEndWith('.pdf');
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

/**
 * Regression guard for the legal-basis (recht) save bug.
 *
 * A migration renamed the column `recht-additional` -> `recht_additional`, but the
 * approvalRules() in the state classes kept the old hyphenated key and Draft's
 * `exists:` rule pointed at a non-existent `App\Models\Legacy\LegalBase` model.
 * As a result the additional field was silently dropped by $validator->validate()
 * (no rule matched the data key) and picking a legal basis in draft state threw an
 * SQL error against a missing table. Both must round-trip through a save.
 */
it('persists the legal basis (recht) and its additional field', function (): void {
    $this->actingAs(adminUser());

    LegalBasis::firstOrCreate(['slug' => 'test-basis'], [
        'label' => 'Test Basis',
        'label_additional' => 'Reference number',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $project = Project::factory()->by(adminUser())->create([
        'name' => 'Recht Project',
        // Must resolve under the `email:rfc,dns` rule on save.
        'responsible' => 'test@open-administration.de',
    ]);
    $project->posts()->create([
        'name' => 'Existing Post',
        'einnahmen' => Money::EUR(0),
        'ausgaben' => Money::EUR(5000),
        'bemerkung' => 'This is a description that is long enough for validation.',
    ]);

    Livewire::test('pages::project.edit-project', ['project_id' => $project->id])
        ->set('recht', 'test-basis')
        ->set('recht_additional', 'INV-2026-001')
        ->call('saveAs', 'draft')
        ->assertHasNoErrors();

    $project->refresh();
    expect($project->recht)->toBe('test-basis')
        ->and($project->recht_additional)->toBe('INV-2026-001');
});

/**
 * Regression guard for the protocol link save bug: the state rules keyed the
 * field as `protocol`, but the data/column is `protokoll`, so $validator->validate()
 * dropped it and the value was never persisted.
 */
it('persists the protocol link', function (): void {
    $project = Project::factory()->by(user())->create([
        'name' => 'Protocol Project',
        'responsible' => 'test@open-administration.de',
    ]);
    $project->posts()->create([
        'name' => 'Existing Post',
        'einnahmen' => Money::EUR(0),
        'ausgaben' => Money::EUR(5000),
        'bemerkung' => 'This is a description that is long enough for validation.',
    ]);

    Livewire::test('pages::project.edit-project', ['project_id' => $project->id])
        ->set('protokoll', 'https://example.com/protocol-2026')
        ->call('saveAs', 'draft')
        ->assertHasNoErrors();

    expect($project->refresh()->protokoll)->toBe('https://example.com/protocol-2026');
});

/**
 * Regression guard for the "Call to a member function isZero() on string" error.
 *
 * The budget table binds <x-money-input wire:model.live.blur="posts.N.einnahmen">,
 * so the browser sends the formatted *string*, and the sibling field's
 * :disabled="!...->isZero()" calls a Money method on it during re-render.
 * This stays safe only while the MoneySynth hydrates nested Money array values,
 * so we assert the type survives string updates across the relevant flows.
 */
it('keeps money post fields as Money after string wire:model updates', function (): void {
    $project = Project::factory()->by(user())->create(['name' => 'Money Repro']);
    $project->posts()->create([
        'name' => 'Existing Post',
        'einnahmen' => Money::EUR(0),
        'ausgaben' => Money::EUR(5000),
        'bemerkung' => 'This is a description that is long enough for validation.',
    ]);

    $component = Livewire::test('pages::project.edit-project', ['project_id' => $project->id]);

    // The browser sends the formatted string, not a Money object.
    $component->set('posts.0.einnahmen', '50,00 €')->assertOk();
    expect($component->get('posts.0.einnahmen'))->toBeInstanceOf(Money::class);

    // Cleared field, cross-field update, and a freshly added row must all stay Money.
    $component->set('posts.0.einnahmen', '')->assertOk();
    $component->set('name', 'Renamed')->assertOk();
    expect($component->get('posts.0.einnahmen'))->toBeInstanceOf(Money::class);

    $component->call('addEmptyPost')->set('posts.1.einnahmen', '12,34 €')->assertOk();
    expect($component->get('posts.1.einnahmen'))->toBeInstanceOf(Money::class);
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

it('rejects saving a project that violates the state rules', function (): void {
    // A brand-new project has empty required fields, so applying it (transition to
    // the 'wip'/Applied state, whose basicRules mark name/org/dates/etc. required)
    // must fail validation and persist nothing.
    $countBefore = Project::count();

    Livewire::test('pages::project.edit-project')
        ->set('hhp_id', $this->budgetPlan->id)
        ->call('saveAs', 'wip')
        ->assertHasErrors();

    expect(Project::count())->toBe($countBefore);
});

/**
 * Create a budget Titel (with its enclosing group) under a plan, so that the
 * same titel_nr can be reproduced across plans to exercise cross-plan mapping.
 */
function budgetItem(LegacyBudgetPlan $plan, string $titelNr, string $name): LegacyBudgetItem
{
    $group = LegacyBudgetGroup::create([
        'hhp_id' => $plan->id,
        'gruppen_name' => 'Gruppe '.$titelNr,
        'type' => 1,
    ]);

    return LegacyBudgetItem::create([
        'hhpgruppen_id' => $group->id,
        'titel_name' => $name,
        'titel_nr' => $titelNr,
        'value' => 1000,
    ]);
}

/**
 * Record an expenditure (in EUR) against a project post via a receipt post, so
 * ProjectPost::expendedSum() reports it as spent.
 */
function spendOnPost(int $postId, float $euros): void
{
    // expendedSum() only sums beleg_posten by projekt_posten_id, so the receipt
    // (beleg_id) just needs to be a non-null value here.
    $erp = new ExpenseReceiptPost(['projekt_posten_id' => $postId, 'ausgaben' => $euros, 'einnahmen' => 0]);
    $erp->beleg_id = 1;
    $erp->short = random_int(1, 30000);
    $erp->save();
}

it('prefills a fresh draft when copying a project', function (): void {
    $item = budgetItem($this->budgetPlan, '5000', 'Material');
    Storage::fake('local');
    // Unique name so the assertions stay stable on the persistent test database.
    $sourceName = 'Source Project '.uniqid();
    $source = Project::factory()->by(user())->create([
        'name' => $sourceName,
        'responsible' => 'test@open-administration.de',
    ]);
    $source->posts()->create([
        'name' => 'Post A', 'bemerkung' => 'This is a description that is long enough.',
        'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(10000), 'titel_id' => $item->id,
    ]);
    $source->posts()->create([
        'name' => 'Post B', 'bemerkung' => 'This is a description that is long enough.',
        'einnahmen' => Money::EUR(5000), 'ausgaben' => Money::EUR(0), 'titel_id' => $item->id,
    ]);
    $sourcePath = 'projects/'.$source->id.'/orig.pdf';
    Storage::put($sourcePath, 'PDF-CONTENT');
    $source->attachments()->create([
        'path' => $sourcePath, 'name' => 'orig.pdf', 'mime_type' => 'application/pdf', 'size' => 1234,
    ]);

    $component = Livewire::test('pages::project.edit-project', ['sourceId' => $source->id, 'sourceKind' => 'copy'])
        ->assertSet('isNew', true)
        ->assertSet('sourceKind', 'copy')
        // The copy stays in the source's own budget plan.
        ->assertSet('hhp_id', $source->relatedBudgetPlan()->id)
        ->assertCount('posts', 2);

    expect($component->get('name'))->toBe($sourceName.' (Kopie)');
    foreach ($component->get('posts') as $post) {
        expect($post)->not->toHaveKey('id')
            ->and($post['titel_id'])->toBe($item->id)
            ->and($post['readonly'])->toBeFalse();
    }

    // Saving must create a second, distinct project (not touch the source).
    $component->call('saveAs', 'draft')->assertHasNoErrors();
    $copy = Project::where('name', $sourceName.' (Kopie)')->sole();
    expect($copy->id)->not->toBe($source->id)
        ->and($copy->posts)->toHaveCount(2)
        // The copy keeps a backlink to its source.
        ->and($copy->source_id)->toBe($source->id)
        ->and($copy->source_kind)->toBe('copy')
        ->and($copy->sourceProject->id)->toBe($source->id);

    // The attachment is duplicated into the copy's own storage; the source stays intact.
    $copyAttachment = $copy->attachments->sole();
    expect($copyAttachment->name)->toBe('orig.pdf')
        ->and($copyAttachment->path)->not->toBe($sourcePath);
    Storage::assertExists($copyAttachment->path);
    Storage::assertExists($sourcePath);
});

it('forbids creating from leftovers unless the project is terminated', function (): void {
    $source = Project::factory()->by(user())->withState('draft')->create();

    Livewire::test('pages::project.edit-project', ['sourceId' => $source->id, 'sourceKind' => 'leftovers'])
        ->assertForbidden();
});

it('carries remaining amounts and remaps titel when creating from leftovers', function (): void {
    $oldItem = budgetItem($this->budgetPlan, '6000', 'Reise');
    $newPlan = LegacyBudgetPlan::create([
        'von' => now()->addYear()->startOfYear(),
        'bis' => now()->addYear()->endOfYear(),
        'state' => 'final',
    ]);
    $newItem = budgetItem($newPlan, '6000', 'Reise');

    $source = Project::factory()->by(user())->withState('terminated')->create(['name' => 'Old Project']);
    $post = $source->posts()->create([
        'name' => 'Travel', 'bemerkung' => 'This is a description that is long enough.',
        'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(10000), 'titel_id' => $oldItem->id,
    ]);
    // 30.00 EUR already spent of the 100.00 EUR -> 70.00 EUR remaining.
    spendOnPost($post->id, 30);

    $component = Livewire::test('pages::project.edit-project', ['sourceId' => $source->id, 'sourceKind' => 'leftovers'])
        ->assertSet('isNew', true)
        ->assertSet('hhp_id', $newPlan->id)
        // Backlink to the source is tracked for persistence.
        ->assertSet('sourceId', $source->id)
        ->assertSet('sourceKind', 'leftovers')
        ->assertCount('posts', 1);

    $carried = $component->get('posts')[0];
    expect($carried['titel_id'])->toBe($newItem->id)
        ->and($carried['titel_id'])->not->toBe($oldItem->id)
        ->and($carried['ausgaben']->getAmount())->toBe('7000');
});

it('empties titel when no match exists in the target plan on leftovers', function (): void {
    $oldItem = budgetItem($this->budgetPlan, '7000', 'Sonstiges');
    $newPlan = LegacyBudgetPlan::create([
        'von' => now()->addYear()->startOfYear(),
        'bis' => now()->addYear()->endOfYear(),
        'state' => 'final',
    ]);
    budgetItem($newPlan, '9999', 'Anderes'); // no matching titel_nr

    $source = Project::factory()->by(user())->withState('terminated')->create(['name' => 'Old Project']);
    $source->posts()->create([
        'name' => 'Stuff', 'bemerkung' => 'This is a description that is long enough.',
        'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(10000), 'titel_id' => $oldItem->id,
    ]);

    $component = Livewire::test('pages::project.edit-project', ['sourceId' => $source->id, 'sourceKind' => 'leftovers'])
        ->assertSet('hhp_id', $newPlan->id)
        ->assertCount('posts', 1);

    expect($component->get('posts')[0]['titel_id'])->toBeNull();
});

it('skips fully spent posts when creating from leftovers', function (): void {
    $oldItem = budgetItem($this->budgetPlan, '8000', 'Mixed');
    $newPlan = LegacyBudgetPlan::create([
        'von' => now()->addYear()->startOfYear(),
        'bis' => now()->addYear()->endOfYear(),
        'state' => 'final',
    ]);
    budgetItem($newPlan, '8000', 'Mixed');

    $source = Project::factory()->by(user())->withState('terminated')->create(['name' => 'Old Project']);
    $spent = $source->posts()->create([
        'name' => 'Spent', 'bemerkung' => 'This is a description that is long enough.',
        'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(5000), 'titel_id' => $oldItem->id,
    ]);
    spendOnPost($spent->id, 50);
    $source->posts()->create([
        'name' => 'Remaining', 'bemerkung' => 'This is a description that is long enough.',
        'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(10000), 'titel_id' => $oldItem->id,
    ]);

    Livewire::test('pages::project.edit-project', ['sourceId' => $source->id, 'sourceKind' => 'leftovers'])
        ->assertCount('posts', 1)
        ->assertSet('posts.0.name', 'Remaining');
});

it('remaps post titel when the budget plan is changed', function (): void {
    $matchedOld = budgetItem($this->budgetPlan, '5500', 'Matched');
    $unmatchedOld = budgetItem($this->budgetPlan, '5600', 'Unmatched');
    $newPlan = LegacyBudgetPlan::create([
        'von' => now()->addYear()->startOfYear(),
        'bis' => now()->addYear()->endOfYear(),
        'state' => 'final',
    ]);
    $matchedNew = budgetItem($newPlan, '5500', 'Matched'); // only this titel_nr exists in the new plan

    $project = Project::factory()->by(user())->create(['name' => 'Switch Plan']);
    $project->posts()->create([
        'name' => 'P1', 'bemerkung' => 'This is a description that is long enough.',
        'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(10000), 'titel_id' => $matchedOld->id,
    ]);
    $project->posts()->create([
        'name' => 'P2', 'bemerkung' => 'This is a description that is long enough.',
        'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(10000), 'titel_id' => $unmatchedOld->id,
    ]);

    $component = Livewire::test('pages::project.edit-project', ['project_id' => $project->id])
        ->set('hhp_id', $newPlan->id);

    $posts = $component->get('posts');
    expect($posts[0]['titel_id'])->toBe($matchedNew->id)
        ->and($posts[1]['titel_id'])->toBeNull();
});
