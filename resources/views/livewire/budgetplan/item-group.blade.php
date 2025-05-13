<div @class([
    'col-span-8 grid grid-cols-subgrid gap-4',
    'border-red-500 border-2' => false,
    //'col-start-2' => $level === 1,
    //'col-start-3' => $level === 2,
    //'col-start-4' => $level === 3,
    'ml-5' => $level === 1,
    'ml-10' => $level === 2,
    'ml-16' => $level === 3,
]) x-sort:item="{{ $item_id }}">
    <div x-sort:handle class="cursor-grab ">
        @if($level > 0)<span class="ml-1 mr-3">˫</span> @endif
        <x-fas-grip-vertical  class="fill-zinc-400 inline-block"/>
    </div>
    <div class="col-span-2">
        <flux:input wire:model.blur="short_name"/>
    </div>
    <div class="col-span-2">
        <flux:input wire:model.blur="name"/>
    </div>
    <div class="col-span-2">
        <flux:input.group @class([
            //'pl-10' => $level === 3,
            //'pl-5' => $level === 2,
        ])>
            <flux:input.group.prefix>
                <span>Σ</span>
            </flux:input.group.prefix>
            <flux:input readonly wire:model="value" />
            <flux:input.group.suffix>€</flux:input.group.suffix>
        </flux:input.group>
    </div>
    <div>
        <flux:dropdown>
            <flux:button variant="subtle" icon:trailing="ellipsis-vertical"></flux:button>
            <flux:menu>
                <flux:menu.item>Upwards?</flux:menu.item>
                <flux:menu.item icon="">to budget?</flux:menu.item>
                <flux:menu.item icon="">copy</flux:menu.item>
                <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>
    <div class="col-span-8 grid grid-cols-subgrid gap-4" x-sort="$wire.$parent.sort($item,$position)">
        @foreach($children as $key => $child)
            @if($child['type'] === 'group')
                <livewire:budgetplan.item :wire:key="$key" :item_id="$child->id"/>
            @else
                <livewire:budgetplan.item :wire:key="$key" :item_id="$child->id"/>
            @endif
        @endforeach
        <div @class([ 'col-start-2 col-span-8 grid grid-cols-subgrid gap-4',
            'ml-10' => $level === 1,
            'ml-20' => $level === 2,
            'ml-30' => $level === 3,
       ])>
            <flux:button wire:click="addBudget({{ $item_id }})" variant="subtle">+ New Budget</flux:button>
        </div>
    </div>
</div>
