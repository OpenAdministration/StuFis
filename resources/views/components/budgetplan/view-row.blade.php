@php use App\Models\BudgetItem; @endphp
@props([
    'item',
    'level' => $item->depth,
])

@php /** @var $item BudgetItem */ @endphp

<tr {{ $attributes->class([
  "odd:bg-gray-50",
  "border-t border-gray-200",
  "border-x-4 border-x-indigo-600" => $level === 0,
  "border-x-4 border-x-indigo-400" => $level === 1,
  "border-x-4 border-x-indigo-200" => $level === 2,
  "border-x-4 border-x-indigo-50 " => $level === 3,
  "text-sm font-medium text-gray-900" => $item->is_group,
  "text-sm whitespace-nowrap text-gray-700" => !$item->is_group,
])}}>
    @if($item->isMount())
        {{-- Mount: read-only reference standing in for another plan's whole in/out side --}}
        <td @class(["text-left", "flex items-center",
            "py-4",
            "px-3 sm:pl-3" => $level === 0,
            "px-3 sm:pl-8" => $level === 1,
            "px-3 sm:pl-13" => $level === 2,
            "px-3 sm:pl-18" => $level === 3,
        ])>
            <x-fas-link class="size-4 mx-2 text-gray-600"/>
            {{ $item->short_name }}
        </td>
        <td @class(["text-left px-3 sm:pl-3 italic"])>
            @if($item->referencedPlan)
                <flux:link :href="route('budget-plan.view', $item->referencedPlan->id)">{{ $item->referencedPlan->label() }}</flux:link>
            @endif
        </td>
        <td @class(["text-base text-right sm:pl-3"])><x-fas-link class="size-4 inline text-indigo-700"/></td>
        <td @class(["text-right px-3 sm:pl-3 font-medium"])>{{ $item->effectiveValue()->format() }}</td>
        <td @class(["text-right px-3 sm:pl-3"])><span class="text-gray-300">–</span></td>
        <td @class(["text-right px-3 sm:pl-3 sm:pr-6"])><span class="text-gray-300">–</span></td>

    @elseif($item->is_group)
        {{-- Is Group ; th needed to make sticky work --}}
        <th @class(["text-left", "flex items-center",
        "py-4",
        "px-3 sm:pl-3" => $level === 0,
        "px-3 sm:pl-8" => $level === 1,
        "px-3 sm:pl-13" => $level === 2,
        "px-3 sm:pl-18" => $level === 3,
    ])>
            <x-fas-wallet class="size-4 mx-2 text-gray-600"/>
            {{ $item->short_name }}
        </th>
        <th @class(["text-left px-3 sm:pl-3", "py-4"])>{{ $item->name }}</th>
        <th @class(["text-base text-right sm:pl-3 font-semibold text-indigo-700"])>
            @if($item->is_group) Σ @endif
        </th>
        <th @class(["text-right px-3  sm:pl-3", "py-4" ])>{{ $item->effectiveValue()->format() }}</th>
        {{-- Ist (booked) / Verfügbar (available) need bookings — placeholders until that lands --}}
        <th @class(["text-right px-3 sm:pl-3", "py-4"])><span class="text-gray-300">–</span></th>
        <th @class(["text-right px-3 sm:pl-3 sm:pr-6", "py-4"])><span class="text-gray-300">–</span></th>

    @else
        {{-- No Group --}}
        <td @class(["text-left", "flex items-center",
            "py-4",
            "px-3 sm:pl-3" => $level === 0,
            "px-3 sm:pl-8" => $level === 1,
            "px-3 sm:pl-13" => $level === 2,
            "px-3 sm:pl-18" => $level === 3,
        ])>
            <x-fas-money-bill class="size-4 mx-2 text-gray-600"/>
            {{ $item->short_name }}
        </td>
        <td @class(["text-left px-3 sm:pl-3",])>{{ $item->name }}</td>
        <td></td>
        <td @class(["text-right px-3  sm:pl-3"])>{{ $item->value->format() }}</td>
        {{-- Ist (booked) / Verfügbar (available) need bookings — placeholders until that lands --}}
        <td @class(["text-right px-3 sm:pl-3"])><span class="text-gray-300">–</span></td>
        <td @class(["text-right px-3 sm:pl-3 sm:pr-6"])><span class="text-gray-300">–</span></td>
    @endif
</tr>
