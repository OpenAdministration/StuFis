<div @class([
    'col-span-8 grid grid-cols-subgrid gap-4',
    'border-red-500 border-2' => false,
    //'col-start-2' => $level === 1,
    //'col-start-3' => $level === 2,
    //'col-start-4' => $level === 3,
    //'mb-10' => $level === 1,
    //'ml-5' => $level === 2,
    //'ml-8' => $level === 3,
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
            <flux:input wire:model.blur="value" x-mask:dynamic="$money($input, ',', ' ')"/>
            <flux:input.group.suffix>€</flux:input.group.suffix>
        </flux:input.group>
    </div>
    <div>
        <flux:dropdown>
            <flux:button variant="subtle" icon:trailing="ellipsis-vertical"></flux:button>
            <flux:menu>
                <flux:menu.item>Upwards?</flux:menu.item>
                <flux:menu.item wire:click="toGroup()" icon="">to group</flux:menu.item>
                <flux:menu.item icon="">copy</flux:menu.item>
                <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>
</div>
