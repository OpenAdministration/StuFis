<?php

use App\Console\Commands\legacy\ConvertLegacyBudgetPlans;

function derivedGroupNr(array $childTitelNrs): ?string
{
    $method = new ReflectionMethod(ConvertLegacyBudgetPlans::class, 'deriveGroupShortName');
    $method->setAccessible(true);

    return $method->invoke(new ConvertLegacyBudgetPlans, $childTitelNrs);
}

function nextFreeGroupNr(string $prefix, array $used): string
{
    $method = new ReflectionMethod(ConvertLegacyBudgetPlans::class, 'nextFreeGroupNumber');
    $method->setAccessible(true);

    return $method->invoke(new ConvertLegacyBudgetPlans, $prefix, $used);
}

it('derives the group number from the parent prefix of its children', function (): void {
    expect(derivedGroupNr(['E.1.1', 'E.1.2']))->toBe('E.1');
    expect(derivedGroupNr(['E.2.1', 'E.2.2']))->toBe('E.2');
});

it('uses the shallowest child when children have mixed depth', function (): void {
    expect(derivedGroupNr(['E.3.1.1', 'E.3.1', 'E.3.1.2']))->toBe('E.3');
});

it('returns null when no child is numbered', function (): void {
    expect(derivedGroupNr([null, '', 'Personalkosten']))->toBeNull();
    expect(derivedGroupNr([]))->toBeNull();
});

it('auto-counts the next free number per type, skipping taken ones', function (): void {
    expect(nextFreeGroupNr('E', []))->toBe('E.1');
    expect(nextFreeGroupNr('E', [1 => true, 2 => true]))->toBe('E.3');
    // fills the first gap so it can't collide with a derived "A.1"/"A.3"
    expect(nextFreeGroupNr('A', [1 => true, 3 => true]))->toBe('A.2');
});
