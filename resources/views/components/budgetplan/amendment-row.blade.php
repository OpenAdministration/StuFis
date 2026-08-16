@php use App\Models\BudgetItem;use App\Models\Enums\BudgetItemChangeAction;use Cknow\Money\Money; @endphp
@props([
    /** annotated by App\Support\Budget\AmendmentTree — carries the before/after figures and the action */
    'item',
])

@php
    /** @var BudgetItem $item */
    $level = $item->tree_level;
    // same indent step as x-budgetplan.view-row, so an amendment's full view reads like a plan
    $indentRem = 0.75 + $level * 1.25;

    $isAdded = $item->amendment_action === BudgetItemChangeAction::Add;
    $isDeleted = $item->amendment_action === BudgetItemChangeAction::Delete;
    $isModified = $item->amendment_action === BudgetItemChangeAction::Modify;

    $before = $item->before_value;
    $after = $item->after_value;
    // the side an item doesn't exist on counts as zero, so an add/delete shows its whole amount
    // as the delta rather than nothing
    $delta = ($after ?? Money::EUR(0))->subtract($before ?? Money::EUR(0));

    // unlike x-budgetplan.view-row, group rows keep <td> figures (only the identity cell is a
    // <th>) — the tint carries the change, so bolding the numbers as well is one signal too many
    $cellClass = 'px-3 sm:pl-3 text-right'.($item->is_group ? ' py-4 font-semibold' : '');
@endphp

<tr
  x-show="!isHidden($el)"
  data-ancestor-ids="@json($item->ancestor_ids)"
  x-transition.opacity.duration.200ms
  x-cloak
  style="--indent: {{ $indentRem }}rem"
  @class([
    "odd:bg-gray-50",
    "border-t border-gray-200",
    "border-x-4 border-x-indigo-600" => $level === 0,
    "border-x-4 border-x-indigo-400" => $level === 1,
    "border-x-4 border-x-indigo-200" => $level === 2,
    "border-x-4 border-x-indigo-50" => $level === 3,
    "text-sm font-medium text-gray-900" => $item->is_group,
    "text-sm whitespace-nowrap text-gray-700" => ! $item->is_group,
    // the change tint has to win over the odd/even striping above; same palette as the editor's rows
    "bg-green-50! dark:bg-green-950/30!" => $isAdded,
    "bg-amber-50! dark:bg-amber-950/30!" => $isModified,
    "bg-red-50! dark:bg-red-950/20!" => $isDeleted,
  ])>
    {{-- Identity cell mirrors x-budgetplan.view-row: a group row is a <th> that collapses its
         subtree on click, everything else a plain cell with the same chevron gutter width. --}}
    @if($item->is_group)
        <th class="text-left flex items-center cursor-pointer select-none py-4 px-3 sm:pl-(--indent)"
            x-on:click="toggle({{ $item->id }})">
            <span class="inline-flex w-4 shrink-0 items-center justify-center me-2">
                <x-fas-chevron-down class="size-3 text-gray-500 transition-transform duration-200"
                                    ::class="collapsed.includes({{ $item->id }}) ? '-rotate-90' : ''"/>
            </span>
            {{ $item->short_name }}
        </th>
    @else
        <td class="text-left flex items-center py-4 px-3 sm:pl-(--indent)">
            <span class="inline-flex w-4 shrink-0 me-2"></span>
            {{ $item->short_name }}
        </td>
    @endif

    <td @class(["text-left px-3 sm:pl-3", "py-4" => $item->is_group])>
        <span class="inline-flex flex-wrap items-center gap-2">
            @if($item->isMount())
                @if($item->referencedPlan)
                    <flux:link :href="route('budget-plan.view', $item->referencedPlan->id)" class="italic">{{ $item->referencedPlan->label() }}</flux:link>
                @endif
            @elseif($item->is_group || $isDeleted)
                {{-- a deleted item is on its way out of the plan: no link into a detail page that
                     is about to stop being part of it --}}
                <span @class(["line-through" => $isDeleted])>{{ $item->name_after }}</span>
            @else
                <flux:link :href="route('budget-plan.item.view', [$item->budget_plan_id, $item->id])" wire:navigate>{{ $item->name_after }}</flux:link>
            @endif

            @if($item->name_before !== null)
                {{-- the rename spelled out inline, so the full view carries the same detail as the diff --}}
                <span class="text-xs text-gray-500 line-through">{{ $item->name_before }}</span>
            @endif

            @if($isAdded)
                <flux:badge color="green" size="sm">{{ __('budget-plan.amendment.change.add') }}</flux:badge>
            @elseif($isDeleted)
                <flux:badge color="red" size="sm">{{ __('budget-plan.amendment.change.delete') }}</flux:badge>
            @elseif($isModified)
                <flux:badge color="amber" size="sm">{{ __('budget-plan.amendment.change.modify') }}</flux:badge>
            @endif

            @if(filled($item->amendment_reason))
                <flux:tooltip :content="$item->amendment_reason">
                    <flux:icon.chat-bubble-bottom-center-text class="size-4 text-gray-400"/>
                </flux:tooltip>
            @endif
        </span>
    </td>

    <td @class(["text-center px-3", "py-4" => $item->is_group])>
        @if($item->isMount())
            <x-fas-link class="size-4 inline text-indigo-600"/>
        @elseif($item->is_group)
            <x-fas-wallet class="size-4 inline text-gray-600"/>
        @else
            <x-fas-money-bill class="size-3.5 inline text-gray-400"/>
        @endif
    </td>

    <td class="{{ $cellClass }}">
        <span @class(["line-through text-gray-500" => $isDeleted])>{{ $before?->format() ?? '—' }}</span>
    </td>
    <td class="{{ $cellClass }}">
        {{-- no extra weight on a changed row's new figure: the tint and the badge already mark it --}}
        <span>{{ $after?->format() ?? '—' }}</span>
    </td>
    <td class="{{ $cellClass }} sm:pr-6">
        @unless($delta->isZero())
            <span @class([
                "text-green-700 dark:text-green-400" => $delta->isPositive(),
                "text-red-700 dark:text-red-400" => $delta->isNegative(),
            ])>{{ $delta->isPositive() ? '+' : '' }}{{ $delta->format() }}</span>
        @endunless
    </td>
</tr>
