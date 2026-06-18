@props([
    'level' => 0,
    'item',
    'lastItem' => [],
])

{{-- children inside --}}
<div @class(["col-span-9 grid grid-cols-subgrid gap-x-4 ",
        //"bg-gray-400" => $level === 0,
        //"bg-gray-300" => $level === 1,
        //"bg-gray-200" => $level === 2,
        //"bg-gray-100" => $level === 3,
])>
    {{-- only this row --}}
    <div class="col-span-9 grid grid-cols-subgrid gap-x-4">
        {{-- Hieracy column --}}
        <div class="col-span-1">
            <div class="flex items-top h-full">
                @for($i = 1; $i <= $level; $i++)
                    <!-- vertical line or spacing -->
                    <div @class([
                        'ml-2',
                        'mr-2' => $i < $level,
                        'h-full border-l-2 border-zinc-300 dark:border-zinc-600' => !($lastItem[$i-1] ?? false),
                        'h-1/2 border-l-2 border-zinc-300 dark:border-zinc-600' => ($lastItem[$i-1] ?? false) && $i === $level,
                    ])></div>
                @endfor
                <!-- horizontal line -->
                @if($level > 0)
                    <div class="h-1/2 w-2 border-b-2 border-zinc-300 dark:border-zinc-600"></div>
                @endif

                @if($item->is_group)
                    <x-fas-wallet @class([
                        'w-5 h-5',
                        'fill-zinc-600 dark:fill-zinc-400' => $level === 0,
                        'fill-zinc-500 dark:fill-zinc-500' => $level > 0,
                    ])/>
                @else
                    <x-fas-money-bill class="w-5 h-5 fill-zinc-400 dark:fill-zinc-600"/>
                @endif
            </div>
        </div>

        {{-- Icon and short title column --}}
        <div class="col-span-1 flex items-center h-full">

            {{-- Short Name Column --}}
            <span @class([
                'font-semibold text-zinc-900 dark:text-zinc-100' => $item->is_group,
                'text-zinc-700 dark:text-zinc-300' => !$item->is_group,
            ])>
                {{ $item->short_name }}
            </span>
        </div>

        {{-- Long Name Column --}}
        <div class="col-span-4 flex items-center">
            <span @class([
                'font-semibold text-zinc-900 dark:text-zinc-100' => $item->is_group,
                'text-zinc-700 dark:text-zinc-300' => !$item->is_group,
            ])>
                {{ $item->name }}
            </span>
        </div>



        {{-- Value Column --}}
        <div class="flex justify-end items-center">
            @if($item->is_group)
                <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $item->value->format() }}
                </span>
            @else
                <span class="font-mono text-zinc-700 dark:text-zinc-300">
                    {{ $item->value->format() }}
                </span>
            @endif
        </div>

        {{-- Booked Column --}}
        <div class="flex justify-end items-center">
            <span class="font-mono text-zinc-500 dark:text-zinc-500">
                {{-- TODO: Implement booked amount calculation --}}
                -
            </span>
        </div>

        {{-- Available Column --}}
        <div class="flex justify-end items-center">
            <span class="font-mono text-zinc-500 dark:text-zinc-500">
                {{-- TODO: Implement available amount calculation --}}
                -
            </span>
        </div>

        {{-- Empty column for alignment --}}
        <div></div>
    </div>

    {{-- Recursively render children with hierarchy tracking --}}
    @if($item->is_group && $item->orderedChildren->isNotEmpty())
        @foreach($item->orderedChildren as $child)
            <x-budgetplan.item-group-view
                :item="$child"
                :level="$level + 1"
                :last-item="[...$lastItem, $loop->last]"
            />
        @endforeach
    @endif
</div>
