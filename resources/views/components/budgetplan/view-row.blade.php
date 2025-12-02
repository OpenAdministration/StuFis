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
    @if($item->is_group)
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
        <th @class(["text-right px-3  sm:pl-3", "py-4" ])>{{ $item->value }}</th>
        <th @class(["text-right px-3 sm:pl-3", "py-4"])>{{ $item->value }}</th>
        <th @class(["text-right px-3 sm:pl-3 sm:pr-6", "py-4"])>{{ $item->value }}</th>

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
        <td @class(["text-right px-3  sm:pl-3"])>{{ $item->value }}</td>
        <td @class(["text-right px-3 sm:pl-3"])>{{ $item->value }}</td>
        <td @class(["text-right px-3 sm:pl-3 sm:pr-6"])>{{ $item->value }}</td>
    @endif

    <!--
        <td class="py-4 pr-3 pl-4 text-sm whitespace-nowrap text-gray-900 sm:pl-6">E1.1</td>
        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">Semesterbeiträge</td>
        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">100k</td>
        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">50k</td>
        <td class="py-4 pr-4 pl-3 text-sm whitespace-nowrap text-gray-500 text-right sm:pr-6">75k</td>
     -->
</tr>

<!--
<tr class="even:bg-gray-50 border-t border-gray-200 border-t border-gray-200 border-x-4 border-x-indigo-600">
                            <th class="py-2 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">E1</th>
                            <th class="py-2 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Laufende Einnahmen</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">100€</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">50€</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">50€</th>
                        </tr>
                        <tr class="even:bg-gray-50 border-x-4 border-x-indigo-400">
                            <td class="py-4 pr-3 pl-4 text-sm whitespace-nowrap text-gray-900 sm:pl-6">E1.1</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">Semesterbeiträge</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">100k</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">50k</td>
                            <td class="py-4 pr-4 pl-3 text-sm whitespace-nowrap text-gray-500 text-right sm:pr-6">75k</td>
                        </tr>
                        <tr class="even:bg-gray-50 border-x-4 border-x-indigo-400">
                            <td class="py-4 pr-3 pl-4 text-sm whitespace-nowrap text-gray-900 sm:pl-6">E1.2</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">Spenden</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">5€</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">50k</td>
                            <td class="py-4 pr-4 pl-3 text-sm whitespace-nowrap text-gray-500 text-right sm:pr-6">175k</td>
                        </tr>
                        <tr class="even:bg-gray-50 border-t border-gray-200 border-t border-gray-200 border-x-4 border-x-indigo-700">
                            <th class="py-2 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">E2</th>
                            <th class="py-2 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Weitere Einnahmen</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">100€</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">50€</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">50€</th>
                        </tr>
                        <tr class="even:bg-gray-50 border-x-4 border-x-indigo-400">
                            <td class="py-4 pr-3 pl-4 text-sm whitespace-nowrap text-gray-900 sm:pl-6">E2.1</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">Semesterbeiträge</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">100k</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">50k</td>
                            <td class="py-4 pr-4 pl-3 text-sm whitespace-nowrap text-gray-500 text-right sm:pr-6">75k</td>
                        </tr>
                        <tr class="even:bg-gray-50 border-t border-gray-200 border-x-4 border-x-indigo-400">
                            <td class="py-2 pr-3 pl-4 text-sm whitespace-nowrap font-semibold text-gray-900 sm:pl-6">E2.2</td>
                            <th class="py-2 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Party Einnahmen</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">100€</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">50€</th>
                            <th class="py-2 pr-3 pl-4 text-right text-sm font-semibold text-gray-900 sm:pl-3">50€</th>
                        </tr>
                        <tr class="even:bg-gray-50 border-x-4 border-x-indigo-200">
                            <td class="py-4 pr-3 pl-6 text-sm whitespace-nowrap text-gray-900 sm:pl-8">E2.2-A</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">FSR A</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">100k</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">50k</td>
                            <td class="py-4 pr-4 pl-3 text-sm whitespace-nowrap text-gray-500 text-right sm:pr-6">75k</td>
                        </tr>
                        <tr class="even:bg-gray-50 border-x-4 border-x-indigo-200">
                            <td class="py-4 pr-3 pl-8 text-sm whitespace-nowrap text-gray-900 sm:pl-8">E2.2-B</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">FSR B</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">100k</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-right">50k</td>
                            <td class="py-4 pr-4 pl-3 text-sm whitespace-nowrap text-gray-500 text-right sm:pr-6">75k</td>
                        </tr>

-->
