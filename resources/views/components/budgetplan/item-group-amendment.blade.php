@php use App\Models\BudgetItem;use App\Models\BudgetItemChange;use Illuminate\Support\Collection; @endphp
@props([
    'level' => 0,
    'item',
    'amendmentId',
    /** @var array<int, \Cknow\Money\Money> */
    'values' => [],
    /** @var Collection<int, BudgetItemChange> */
    'changes',
    /** @var bool[] */
    'lastItem' => [],
])

@php
    /** @var BudgetItem $item */
    $change = $changes->get($item->id);
    $isAdded = $item->budget_plan_id === $amendmentId;
    $isDeleted = $change?->action === BudgetItemChange::ACTION_DELETE;
    $isModified = $change?->action === BudgetItemChange::ACTION_MODIFY;
@endphp

<div @class([
        "col-span-8 grid grid-cols-subgrid",
        "opacity-60" => $isDeleted,
    ]) wire:sort:item="{{ $item->id }}">
    <div @class([
            "col-span-8 grid grid-cols-subgrid rounded",
            "bg-green-50 dark:bg-green-950/30" => $isAdded && ! $isDeleted,
            "bg-amber-50 dark:bg-amber-950/30" => $isModified && ! $isDeleted,
            "bg-red-50 dark:bg-red-950/20" => $isDeleted,
        ])>
        <div wire:sort:handle @class(["cursor-grab flex items-center justify-end", "my-2"])>
            <x-fas-grip-vertical class="fill-zinc-400 h-5 w-5"/>
            @if($item->is_group)
                <x-fas-wallet class="fill-zinc-600 w-5 h-5 ml-3"/>
            @else
                <x-fas-money-bill class="fill-zinc-400 w-5 h-5 ml-3"/>
            @endif
        </div>
        <div class="col-span-1 my-2">
            <flux:input wire:model.live.blur="items.{{$item->id}}.short_name" :disabled="$isDeleted"/>
        </div>
        <div class="col-span-3 my-2 flex items-center gap-2">
            <flux:input class="flex-1" wire:model.live.blur="items.{{$item->id}}.name" :disabled="$isDeleted"/>
            @if($isAdded)
                <flux:badge color="green" size="sm">{{ __('budget-plan.amendment.change.add') }}</flux:badge>
            @elseif($isDeleted)
                <flux:badge color="red" size="sm">{{ __('budget-plan.amendment.change.delete') }}</flux:badge>
            @elseif($isModified)
                <flux:badge color="amber" size="sm">{{ __('budget-plan.amendment.change.modify') }}</flux:badge>
            @endif
        </div>
        <div class="col-span-2 flex items-center">
            @if($level > 0)
                <div class="flex items-top h-full">
                    @for($i = 1; $i <= $level; $i++)
                        <div @class([
                            "ml-5",
                            "mr-4" => $i < $level,
                            "h-full border-l-2 border-gray-300" => $i < $level && !($lastItem[$i-1]),
                            "border-l-2 border-gray-300 -mt-2 h-[calc(100%+0.5rem)]" => $i === $level && !($lastItem[$i-1]),
                            "border-l-2 border-gray-300 -mt-2 h-[calc(50%+0.5rem)]" => $i === $level && ($lastItem[$i-1]),
                        ])></div>
                    @endfor
                    <div class="h-1/2 w-4 border-b-2 border-gray-300"></div>
                </div>
            @endif
            @if($item->is_group)
                <flux:input.group class="my-2">
                    <flux:input.group.prefix variant="filled">
                        <span>Σ</span>
                    </flux:input.group.prefix>
                    <flux:input readonly variant="filled" value="{{ $values[$item->id]->format() }}"
                                class:input="text-right text-black!"/>
                </flux:input.group>
            @else
                <x-money-input class="my-2 w-full" wire:model.live.blur="items.{{$item->id}}.value" :disabled="$isDeleted"/>
            @endif
        </div>
        <div class="my-2 flex items-center">
            @if($isDeleted)
                <flux:button size="sm" variant="ghost" icon="arrow-uturn-left"
                             wire:click="undoDelete({{ $item->id }})">
                    {{ __('budget-plan.amendment.undo-delete') }}
                </flux:button>
            @else
                <flux:dropdown>
                    <flux:button variant="ghost" icon="ellipsis-horizontal"
                                 :aria-label="__('budget-plan.edit.more-actions')"/>
                    <flux:menu>
                        <flux:menu.submenu :heading="__('budget-plan.edit.transform')" icon="arrows-right-left">
                            <flux:tooltip :content="__('budget-plan.amendment.not-changeable-in-amendment')">
                                <div>
                                    <flux:menu.item disabled>{{ __('budget-plan.edit.to-group') }}</flux:menu.item>
                                </div>
                            </flux:tooltip>
                            <flux:tooltip :content="__('budget-plan.amendment.not-changeable-in-amendment')">
                                <div>
                                    <flux:menu.item disabled>{{ __('budget-plan.edit.to-mount') }}</flux:menu.item>
                                </div>
                            </flux:tooltip>
                        </flux:menu.submenu>
                        <flux:menu.separator/>
                        <flux:menu.item wire:click="sort({{$item->id}}, {{ $item->position - 1 }})"
                                        icon="arrow-up">{{ __('budget-plan.edit.move-up') }}</flux:menu.item>
                        <flux:menu.item wire:click="sort({{$item->id}}, {{ $item->position + 1 }})"
                                        icon="arrow-down">{{ __('budget-plan.edit.move-down') }}</flux:menu.item>
                        @if(! $isAdded && $item->hasBookings())
                            <flux:tooltip :content="__('budget-plan.edit.has-bookings')">
                                <div>
                                    <flux:menu.item icon="trash" variant="danger" disabled>{{ __('budget-plan.edit.delete') }}</flux:menu.item>
                                </div>
                            </flux:tooltip>
                        @else
                            <flux:menu.item wire:click="delete({{ $item->id }})"
                                            :disabled="$item->orderedChildren->isNotEmpty()" variant="danger"
                                            icon="trash">{{ __('budget-plan.edit.delete') }}</flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
                @if($item->is_group)
                    <flux:button icon="plus-money-bill" wire:click="addBudget({{ $item->id }})" variant="ghost"/>
                    @if($level < BudgetItem::MAX_DEPTH - 1)
                        <flux:button icon="plus-wallet" wire:click="addSubGroup({{ $item->id }})" variant="ghost"/>
                    @endif
                @endif
            @endif
        </div>
    </div>
    @if($item->is_group)
        <div class="col-span-8 grid grid-cols-subgrid" wire:sort="sort">
            @foreach($item->orderedChildren as $child)
                <x-budgetplan.item-group-amendment
                    :item="$child"
                    :values="$values"
                    :changes="$changes"
                    :amendment-id="$amendmentId"
                    :wire:key="$child->id"
                    :level="$level + 1"
                    :last-item="[...$lastItem, $loop->last]"
                />
            @endforeach
        </div>
    @endif
</div>
