@props([
    'level' => 0,
    'item',
])

<div @class([
        "col-span-8 grid grid-cols-subgrid gap-y-0",
        //'col-start-2' => $level === 1,
        //'col-start-3' => $level === 2,
        //'col-start-4' => $level === 3,
        //'ml-5' => $level === 1,
        //'ml-10' => $level === 2,
        //'ml-16' => $level === 3,
        //"border-zinc-300 ",
        //"border-l-1" => $level === 0 && $item->is_group,
        //"border-l-2" => $level === 1 && $item->is_group,
        //"border-l-3" => $level === 2 && $item->is_group,
        //"border-l-4" => $level === 3 && $item->is_group,

    ]) x-sort:item="{{ $item->id }}">
    <div @class(["col-span-8 grid grid-cols-subgrid",
            "py-2" => $item->is_group,
            "rounded" => $item->is_group,
            "bg-zinc-300 my-2" => $item->is_group,
        ])>
        <div x-sort:handle @class([
                "cursor-grab flex items-center justify-end"
            ])>
            <x-fas-grip-vertical  class="fill-zinc-400 h-5 w-5"/>
            @if($item->is_group)
                <x-fas-wallet class="fill-zinc-500 w-5 h-5 ml-3"/>
            @else
                <x-fas-money-bill class="fill-zinc-500 w-5 h-5 ml-3"/>
            @endif
        </div>
        <div class="col-span-2">
            <flux:input wire:model.blur="items.{{$item->id}}.short_name"/>
        </div>
        <div class="col-span-2">
            <flux:input wire:model.blur="items.{{$item->id}}.name"/>
        </div>
        <div class="col-span-2">
            <flux:input.group @class([
                    //'pl-10' => $level === 3,
                    //'pl-5' => $level === 2,
                ])>
                @if($item->is_group)
                    <flux:input.group.prefix>
                        <span>Σ</span>
                    </flux:input.group.prefix>
                @endif
                <x-money-input wire:model="items.{{$item->id}}.value" :value="$item->value" :disabled="$item->is_group"/>
                <flux:input.group.suffix>€</flux:input.group.suffix>
            </flux:input.group>
        </div>
        <div>{{-- Action Buttons --}}
            @if($item->is_group)
                <flux:button icon="plus-wallet" wire:click="addSubGroup({{ $item->id }})" variant="ghost"/> {{-- subtle or ghost --}}
                <flux:button icon="plus-money-bill" wire:click="addBudget({{ $item->id }})" variant="ghost"/>
            @endif
            <flux:dropdown>
                <flux:button variant="ghost" icon:trailing="ellipsis-vertical"></flux:button>
                <flux:menu>
                    <flux:menu.item>Debug: L{{ $level }} id{{$item->id}} P{{$item->position}}</flux:menu.item>
                    @if($item->is_group)
                        <flux:menu.item wire:click="convertToBudget({{$item->id}})" :disabled="$item->children->count() > 0" icon="arrows-right-left">to budget</flux:menu.item>
                    @else
                        <flux:menu.item wire:click="convertToGroup({{$item->id}})" icon="arrows-right-left"  >to group</flux:menu.item>
                    @endif
                    <flux:menu.item wire:click="sort({{$item->id}}, {{ $item->position - 1 }})" icon="arrow-up"  >item up</flux:menu.item>
                    <flux:menu.item wire:click="sort({{$item->id}}, {{ $item->position + 1 }})" icon="arrow-down"  >item down</flux:menu.item>
                    <flux:menu.item wire:click="copyItem({{ $item->id }})" icon="clipboard">copy</flux:menu.item>
                    <flux:menu.item wire:click="copyInverse({{ $item->id }})" :disabled="!is_null($item->parent_id)" icon="clipboard">
                        copy zur anderen seite
                    </flux:menu.item>
                    <flux:menu.item wire:click="delete({{ $item->id }})" :disabled="$item->orderedChildren()->count() !== 0" variant="danger" icon="trash">Delete</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
    @if($item->is_group)
        <div @class([
                    "col-span-8 grid grid-cols-subgrid gap-y-4",
                    "border-zinc-300 ",
                    "border-l-12" => $level === 0 && $item->is_group,
                    "border-l-16" => $level === 1 && $item->is_group,
                    "border-l-24" => $level === 2 && $item->is_group,
                    "border-l-28" => $level === 3 && $item->is_group,

                ]) x-sort="$wire.sort($item,$position)">
            @foreach($item->orderedChildren as $child)
                <x-item-group :item="$child" :wire:key="$child->id" :level="$level +1" />
            @endforeach
        </div>
    @endif
</div>
