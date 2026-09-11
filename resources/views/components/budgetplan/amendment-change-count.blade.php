@php use App\Models\Enums\BudgetItemChangeAction; @endphp
@props([
    /** @var array{total: int, add: int, modify: int, delete: int} from App\Support\Budget\AmendmentTree::changeCounts() */
    'counts',
])

@php
    // The breakdown goes into a plain title attribute rather than a flux:tooltip: this badge sits
    // INSIDE the tab's <button>, and nesting another interactive element in there would fight the
    // tab for hover/click.
    $breakdown = collect(BudgetItemChangeAction::cases())
        ->filter(fn (BudgetItemChangeAction $action): bool => ($counts[$action->value] ?? 0) > 0)
        ->map(fn (BudgetItemChangeAction $action): string => $counts[$action->value].' '.$action->label())
        ->implode(' · ');
@endphp

{{-- How many titles this side changes. Deliberately ONE amber badge instead of a green/amber/red
     triple: the tab strip only has to say "something changed here, this much" — which title changed
     how is what the tinted rows and the delta summary below are for. --}}
@if($counts['total'] > 0)
    <flux:badge color="amber" size="sm" :title="$breakdown">{{ $counts['total'] }}</flux:badge>
@endif
