<?php

namespace Tests\Pest\Settings;

use App\Models\Legacy\Project;
use App\Models\LegalBasis;
use Livewire\Livewire;

// All test rows use a "test-lb-" slug prefix so the shared, persisted DB can be
// cleaned up without disturbing the seeded legal bases.
afterEach(function (): void {
    Project::where('recht', 'like', 'test-lb-%')->delete();
    LegalBasis::where('slug', 'like', 'test-lb-%')->delete();
});

/** Index of the row with the given slug in the component's legalBases state. */
function legalBasisIndex(object $component, string $slug): int|false
{
    return collect($component->get('legalBases'))->search(fn (array $row): bool => $row['slug'] === $slug);
}

it('lets an admin add a legal basis', function (): void {
    $this->actingAs(adminUser());

    $component = Livewire::test('pages::settings');
    $index = count($component->get('legalBases'));

    $component->call('addLegalBasis')
        ->set("legalBases.{$index}.slug", 'test-lb-new')
        ->set("legalBases.{$index}.label", 'Test-Grundlage')
        ->set("legalBases.{$index}.label_additional", 'Zusatz')
        ->call('save')
        ->assertHasNoErrors();

    $basis = LegalBasis::where('slug', 'test-lb-new')->first();
    expect($basis)->not->toBeNull()
        ->and($basis->label)->toBe('Test-Grundlage')
        ->and($basis->label_additional)->toBe('Zusatz')
        ->and($basis->is_active)->toBeTrue();
});

it('rejects a malformed slug', function (): void {
    $this->actingAs(adminUser());

    $component = Livewire::test('pages::settings');
    $index = count($component->get('legalBases'));

    $component->call('addLegalBasis')
        ->set("legalBases.{$index}.slug", 'Not A Slug!')
        ->set("legalBases.{$index}.label", 'X')
        ->call('save')
        ->assertHasErrors("legalBases.{$index}.slug");
});

it('rejects a duplicate slug', function (): void {
    LegalBasis::create(['slug' => 'test-lb-dup', 'label' => 'Vorhanden', 'sort_order' => 90, 'is_active' => true]);

    $this->actingAs(adminUser());

    $component = Livewire::test('pages::settings');
    $index = count($component->get('legalBases'));

    $component->call('addLegalBasis')
        ->set("legalBases.{$index}.slug", 'test-lb-dup')
        ->set("legalBases.{$index}.label", 'Doppelt')
        ->call('save')
        ->assertHasErrors("legalBases.{$index}.slug");
});

it('deletes an unreferenced legal basis on save', function (): void {
    LegalBasis::create(['slug' => 'test-lb-del', 'label' => 'Weg damit', 'sort_order' => 91, 'is_active' => true]);

    $this->actingAs(adminUser());

    $component = Livewire::test('pages::settings');
    $index = legalBasisIndex($component, 'test-lb-del');

    $component->call('removeLegalBasis', $index)
        ->call('save')
        ->assertHasNoErrors();

    expect(LegalBasis::where('slug', 'test-lb-del')->exists())->toBeFalse();
});

it('reorders legal bases when one is dragged', function (): void {
    LegalBasis::create(['slug' => 'test-lb-a', 'label' => 'A', 'sort_order' => 80, 'is_active' => true]);
    LegalBasis::create(['slug' => 'test-lb-b', 'label' => 'B', 'sort_order' => 81, 'is_active' => true]);

    $this->actingAs(adminUser());

    $component = Livewire::test('pages::settings');
    $rows = collect($component->get('legalBases'));
    $aIndex = $rows->search(fn (array $row): bool => $row['slug'] === 'test-lb-a');
    $bKey = $rows->firstWhere('slug', 'test-lb-b')['_key'];

    // Drag B (currently after A) onto A's position.
    $component->call('sortLegalBases', $bKey, $aIndex);

    $after = collect($component->get('legalBases'))->pluck('slug');
    expect($after->search('test-lb-b'))->toBeLessThan($after->search('test-lb-a'));
});

it('keeps a legal basis that is still referenced by a project', function (): void {
    LegalBasis::create(['slug' => 'test-lb-used', 'label' => 'In Benutzung', 'sort_order' => 92, 'is_active' => true]);
    Project::factory()->by(user())->create(['recht' => 'test-lb-used']);

    $this->actingAs(adminUser());

    $component = Livewire::test('pages::settings');
    $index = legalBasisIndex($component, 'test-lb-used');

    $component->call('removeLegalBasis', $index);

    // The row stays in the editor and is not removed from the DB.
    expect(collect($component->get('legalBases'))->pluck('slug'))->toContain('test-lb-used')
        ->and(LegalBasis::where('slug', 'test-lb-used')->exists())->toBeTrue();
});
