@props([
    'level' => 0,
    'item',
    /* @var bool array of booleans, one for each level, indicating if the item is the last one on that level  */
    'lastItem' => [],
])

<div @class([
        "col-span-8 grid grid-cols-subgrid",
    ]) wire:sort:item="{{ $item->id }}">
    <div @class(["col-span-8 grid grid-cols-subgrid",
            //"py-2" => $item->is_group,
            //"rounded" => $item->is_group,
            //"bg-zinc-300 my-2" => $item->is_group,
        ])>
        <div wire:sort:handle @class([
                "cursor-grab flex items-center justify-end",
                "my-2"
            ])>
            <x-fas-grip-vertical  class="fill-zinc-400 h-5 w-5"/>
            @if($item->isMount())
                <x-fas-link class="fill-indigo-500 w-5 h-5 ml-3"/>
            @elseif($item->is_group)
                <x-fas-wallet class="fill-zinc-600 w-5 h-5 ml-3"/>
            @else
                <x-fas-money-bill class="fill-zinc-400 w-5 h-5 ml-3"/>
            @endif
        </div>
        <div class="col-span-1 my-2">
            <flux:input wire:model.live.blur="items.{{$item->id}}.short_name"/>
        </div>
        <div class="col-span-3 my-2">
            @if($item->isMount())
                {{-- a mount stands in for another plan; its label links to that plan --}}
                <div class="flex h-full items-center px-3 text-sm">
                    @if($item->referencedPlan)
                        <flux:link :href="route('budget-plan.view', $item->referencedPlan->id)">{{ $item->referencedPlan->label() }}</flux:link>
                    @endif
                </div>
            @else
                <flux:input wire:model.live.blur="items.{{$item->id}}.name"/>
            @endif
        </div>
        <div class="col-span-2 flex items-center">
            @if($level > 0)
                <div class="flex items-top h-full">
                    @for($i = 1; $i <= $level; $i++)
                        <!-- vertical line: ancestor pass-through, or the immediate connector
                             (extended up ~half a row so it reaches the parent box in the row above) -->
                        <div @class([
                            "ml-5",
                            "mr-4" => $i < $level,
                            // ancestor pass-through line (continues full height through this row)
                            "h-full border-l-2 border-gray-300" => $i < $level && !($lastItem[$i-1]),
                            // immediate connector, this item is NOT last: full height + reach up to the parent box's bottom edge
                            "border-l-2 border-gray-300 -mt-2 h-[calc(100%+0.5rem)]" => $i === $level && !($lastItem[$i-1]),
                            // immediate connector, this item IS last: stop at the middle (the elbow) + reach up to the parent box's bottom edge
                            "border-l-2 border-gray-300 -mt-2 h-[calc(50%+0.5rem)]" => $i === $level && ($lastItem[$i-1]),
                        ])></div>
                    @endfor
                    <!-- horizontal line -->
                    <div class="h-1/2 w-4 border-b-2 border-gray-300"></div>
                </div>
            @endif
            @if($item->isMount())
                {{-- mount: read-only rolled-up total of the referenced plan's side (derived).
                     Styled like a group sum, but with a link prefix instead of Σ. --}}
                <flux:input.group class="my-2">
                    <flux:input.group.prefix variant="filled">
                        <x-fas-link class="size-3.5"/>
                    </flux:input.group.prefix>
                    <flux:input readonly variant="filled" value="{{ $item->effectiveValue()->format() }}" class:input="text-right text-black!"/>
                </flux:input.group>
            @elseif($item->is_group)
                {{-- group rows use an input-group so the Σ prefix welds onto the (readonly) sum.
                     Shown live via effectiveValue so a nested mount's derived total rolls up. --}}
                <flux:input.group class="my-2">
                    <flux:input.group.prefix variant="filled">
                        <span>Σ</span>
                    </flux:input.group.prefix>
                    <flux:input readonly variant="filled" value="{{ $item->effectiveValue()->format() }}" class:input="text-right text-black!"/>
                </flux:input.group>
            @else
                {{-- child rows have no prefix; a lone input must NOT sit in an input-group, otherwise
                     the trailing flux:error counts as the last child and strips the input's right rounding --}}
                <x-money-input class="my-2 w-full" wire:model.live.blur="items.{{$item->id}}.value" :disabled="false" />
            @endif
        </div>
        <div class="my-2">{{-- Action Buttons --}}
            <flux:dropdown>
                <flux:button variant="ghost" icon="ellipsis-horizontal" :aria-label="__('budget-plan.edit.more-actions')" />
                <flux:menu>
                    <flux:menu.submenu :heading="__('budget-plan.edit.transform')" icon="arrows-right-left">
                        @if($item->isMount())
                            <flux:menu.item wire:click="convertToBudget({{$item->id}})">{{ __('budget-plan.edit.to-budget') }}</flux:menu.item>
                        @elseif($item->is_group)
                            <flux:menu.item wire:click="convertToBudget({{$item->id}})" :disabled="$item->children->count() > 0">{{ __('budget-plan.edit.to-budget') }}</flux:menu.item>
                        @else
                            <flux:menu.item wire:click="convertToGroup({{$item->id}})">{{ __('budget-plan.edit.to-group') }}</flux:menu.item>
                            {{-- open the modal instantly (client-side) so the skeleton shows while candidates load --}}
                            <flux:menu.item x-on:click="$dispatch('modal-show', { name: 'mount-plan' })" wire:click="openMountPicker({{$item->id}})">{{ __('budget-plan.edit.to-mount') }}</flux:menu.item>
                        @endif
                    </flux:menu.submenu>
                    <flux:menu.separator/>
                    <flux:menu.item wire:click="sort({{$item->id}}, {{ $item->position - 1 }})" icon="arrow-up">{{ __('budget-plan.edit.move-up') }}</flux:menu.item>
                    <flux:menu.item wire:click="sort({{$item->id}}, {{ $item->position + 1 }})" icon="arrow-down">{{ __('budget-plan.edit.move-down') }}</flux:menu.item>
                    <flux:menu.item wire:click="copyItem({{ $item->id }})" icon="clipboard">{{ __('budget-plan.edit.copy') }}</flux:menu.item>
                    <flux:menu.item wire:click="copyInverse({{ $item->id }})" :disabled="!is_null($item->parent_id)" icon="clipboard">
                        {{ __('budget-plan.edit.copy-inverse') }}
                    </flux:menu.item>
                    <flux:menu.item wire:click="delete({{ $item->id }})" :disabled="$item->orderedChildren()->count() !== 0" variant="danger" icon="trash">{{ __('budget-plan.edit.delete') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            @if($item->is_group)
                <flux:button icon="plus-money-bill" wire:click="addBudget({{ $item->id }})" variant="ghost"/>
                @if($level < 2)
                    <flux:button icon="plus-wallet" wire:click="addSubGroup({{ $item->id }})" variant="ghost"/> {{-- subtle or ghost --}}
                @endif
            @endif
        </div>
    </div>
    @if($item->is_group)
        <div @class([
                    "col-span-8 grid grid-cols-subgrid",
                    //"border-zinc-300 ",
                    //"border-l-12" => $level === 0,
                    //"border-l-16" => $level === 1,
                    //"border-l-24" => $level === 2,
                    //"border-l-28" => $level === 3,
                ]) wire:sort="sort">
            @foreach($item->orderedChildren as $child)
                <x-budgetplan.item-group-edit
                    :item="$child"
                    :wire:key="$child->id"
                    :level="$level +1"
                    :last-item="[...$lastItem, $loop->last]"
                />
            @endforeach
        </div>
    @endif
</div>
