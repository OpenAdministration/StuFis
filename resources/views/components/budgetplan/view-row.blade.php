@php use App\Models\BudgetItem; @endphp
@props([
    'item',
    'level' => $item->depth,
])

@php /** @var $item BudgetItem */ @endphp

@php
    // one consistent indent step per nesting level (0.75rem base + 1.25rem/level), computed so
    // the tree stays legible at any depth instead of hard-capping at a handful of levels
    $indentRem = 0.75 + $level * 1.25;

    // group rows render their figures in <th> (browser-default bold sets groups apart) with extra
    // vertical padding; everything else is a plain <td>. Used by the shared figure columns below.
@endphp

<tr
  x-show="!isHidden(@js($item->ancestorIds()))"
  x-transition.opacity.duration.200ms
  x-cloak
  style="--indent: {{ $indentRem }}rem"
  {{ $attributes->class([
  "odd:bg-gray-50",
  "border-t border-gray-200",
  "border-x-4 border-x-indigo-600" => $level === 0,
  "border-x-4 border-x-indigo-400" => $level === 1,
  "border-x-4 border-x-indigo-200" => $level === 2,
  "border-x-4 border-x-indigo-50 " => $level === 3,
  "text-sm font-medium text-gray-900" => $item->is_group,
  "text-sm whitespace-nowrap text-gray-700" => !$item->is_group,
])}}>
    {{-- Identity cells (Titelnummer, Titelname, kind icon) differ per row kind, so each kind is
         spelled out in full for readability. Group rows use <th>: its browser-default bold weight
         is what visually sets groups apart (nothing sticky depends on it). --}}
    @if($item->isMount())
        {{-- Mount: read-only reference standing in for another plan's whole in/out side --}}
        <td class="text-left flex items-center py-4 px-3 sm:pl-(--indent)">
            {{-- empty chevron gutter: keeps names aligned with collapsible group rows --}}
            <span class="inline-flex w-4 shrink-0 me-2"></span>
            {{ $item->short_name }}
        </td>
        <td @class(["text-left px-3 sm:pl-3 italic"])>
            @if($item->referencedPlan)
                <flux:link :href="route('budget-plan.view', $item->referencedPlan->id)">{{ $item->referencedPlan->label() }}</flux:link>
            @endif
        </td>
        <td @class(["text-center px-3"])><x-fas-link class="size-4 inline text-indigo-600"/></td>

    @elseif($item->is_group)
        {{-- Group: clicking the row collapses/expands its descendants (Alpine scope on the tbody) --}}
        <th class="text-left flex items-center cursor-pointer select-none py-4 px-3 sm:pl-(--indent)"
            x-on:click="toggle({{ $item->id }})">
            {{-- chevron gutter: fixed width so leaf/mount siblings line up with the group name --}}
            <span class="inline-flex w-4 shrink-0 items-center justify-center me-2">
                <x-fas-chevron-down class="size-3 text-gray-500 transition-transform duration-200"
                                    ::class="collapsed.includes({{ $item->id }}) ? '-rotate-90' : ''"/>
            </span>
            {{ $item->short_name }}
        </th>
        <th @class(["text-left px-3 sm:pl-3 py-4"])>{{ $item->name }}</th>
        <th @class(["text-center px-3 py-4"])><x-fas-wallet class="size-4 inline text-gray-600"/></th>

    @else
        {{-- Leaf: a plain budget line, links to its detail page --}}
        <td class="text-left flex items-center py-4 px-3 sm:pl-(--indent)">
            <span class="inline-flex w-4 shrink-0 me-2"></span>
            {{ $item->short_name }}
        </td>
        <td @class(["text-left px-3 sm:pl-3"])>
            <flux:link :href="route('budget-plan.item.view', [$item->budget_plan_id, $item->id])" wire:navigate>{{ $item->name }}</flux:link>
        </td>
        <td @class(["text-center px-3"])><x-fas-money-bill class="size-3.5 inline text-gray-400"/></td>
    @endif

    {{-- planned / booked / committed: identical layout across row kinds, so shared (mount emphasises its total) --}}
    @if($item->is_group)
        <th @class(["text-right px-3 sm:pl-3", "py-4"])>{{ $item->planned->format() }}</th>
        <th @class(["text-right px-3 sm:pl-3", "py-4"])>{{ $item->booked->format() }}</th>
        <th @class(["text-right px-3 sm:pl-3 sm:pr-6", "py-4"])>{{ $item->committed->format() }}</th>
    @else
        <td @class(["text-right px-3 sm:pl-3", "font-medium" => $item->isMount()])>{{ $item->planned->format() }}</td>
        <td @class(["text-right px-3 sm:pl-3"])>{{ $item->booked->format() }}</td>
        <td @class(["text-right px-3 sm:pl-3 sm:pr-6"])>{{ $item->committed->format() }}</td>
    @endif
</tr>
