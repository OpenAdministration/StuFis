@php use App\Models\BudgetItem;use App\Models\BudgetItemChange;use App\Models\Enums\BudgetItemChangeAction;use Cknow\Money\Money; @endphp
@props([
    /** @var BudgetItemChange */
    'change',
    /**
     * The item the change points at. Optional — it defaults to the change's own relation, but a
     * caller that already holds the item (⚡item-view is about exactly one) should pass it in
     * rather than have every row lazy-load the same row again.
     *
     * @var BudgetItem|null
     */
    'item' => null,
])

@php
    /** @var BudgetItemChange $change */
    $item ??= $change->budgetItem;
@endphp

{{-- What a single change row actually does, spelled out: the touched fields with their from -> to
     for a modify, and the value at stake for an add/delete. Shared by the amendment's diff view
     (⚡plan-view) and the title detail's amendment hint (⚡item-view), so both always describe a
     change the same way.

     Laid out as a label/value grid rather than a bullet list: the old value reads as struck-out
     and muted, the new one as the emphasised figure, and money columns line up under each other
     (tabular-nums) even when a change touches several fields at once. --}}
@if($change->action === BudgetItemChangeAction::Modify && filled($change->diff))
    <dl class="grid grid-cols-[max-content_1fr] items-baseline gap-x-3 gap-y-1 text-sm">
        @foreach($change->diff as $field => $pair)
            <dt class="text-gray-500">{{ __('budget-plan.amendment.field.'.$field) }}</dt>
            <dd class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                @if($field === 'value')
                    @php
                        $from = Money::EUR((int) $pair['from']);
                        $to = Money::EUR((int) $pair['to']);
                        $delta = $to->subtract($from);
                    @endphp
                    <span class="text-gray-500 line-through tabular-nums">{{ $from->format() }}</span>
                    <span class="text-gray-400" aria-hidden="true">→</span>
                    {{-- the struck-out, muted old value is what marks the new one as current;
                         bolding it on top of that is one emphasis too many --}}
                    <span class="tabular-nums text-gray-900">{{ $to->format() }}</span>
                    @unless($delta->isZero())
                        <flux:badge size="sm" :color="$delta->isNegative() ? 'red' : 'green'">
                            {{ $delta->isPositive() ? '+' : '' }}{{ $delta->format() }}
                        </flux:badge>
                    @endunless
                @else
                    <span class="text-gray-500 line-through">{{ $pair['from'] }}</span>
                    <span class="text-gray-400" aria-hidden="true">→</span>
                    <span class="text-gray-900">{{ $pair['to'] }}</span>
                @endif
            </dd>
        @endforeach
    </dl>
@elseif($item !== null && ! $item->is_group && ! $item->isMount())
    {{-- a group carries no value of its own (it is the live sum of its children), so an added or
         deleted group has no amount worth naming here --}}
    <dl class="grid grid-cols-[max-content_1fr] items-baseline gap-x-3 text-sm">
        <dt class="text-gray-500">{{ __('budget-plan.amendment.field.value') }}</dt>
        <dd class="flex flex-wrap items-baseline gap-x-2">
            @if($change->action === BudgetItemChangeAction::Add)
                <span class="tabular-nums text-gray-900">{{ $item->value?->format() ?? '—' }}</span>
                <flux:text class="text-sm">{{ __('budget-plan.amendment.change-detail.added') }}</flux:text>
            @elseif($change->action === BudgetItemChangeAction::Delete)
                <span class="text-gray-500 line-through tabular-nums">{{ $item->value?->format() ?? '—' }}</span>
                <flux:text class="text-sm">{{ __('budget-plan.amendment.change-detail.removed') }}</flux:text>
            @endif
        </dd>
    </dl>
@endif
