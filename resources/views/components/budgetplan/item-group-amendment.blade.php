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
    // F1 (OP#581): highlight the CONCRETE changed field, not just the row tint — per-field pairs,
    // so a modify that only touches e.g. `value` doesn't also ring-highlight an untouched `name`.
    $nameChange = $isModified ? $change->fieldChange('name') : null;
    $valueChange = $isModified ? $change->fieldChange('value') : null;
@endphp

{{--
    wire:key is set explicitly here (not just at the recursive x-budgetplan.item-group-amendment
    call site below) because a Blade anonymous component only forwards its caller's attributes
    (including :wire:key) onto the root element when the template echoes $attributes somewhere —
    this component never does, so that outer wire:key was silently dropped, leaving Livewire's
    morphdom with no stable per-row identity. That's the root cause of B2 (deleting an unbooked
    base item did nothing visible): without a key, the $isDeleted-driven swap between the
    dropdown-menu and the undo-button (a structurally very different subtree) could get
    morph-patched onto the wrong sibling row instead of the row that was actually deleted.
--}}
<div @class([
        "col-span-8 grid grid-cols-subgrid",
        "opacity-60" => $isDeleted,
    ]) wire:sort:item="{{ $item->id }}" wire:key="budget-item-{{ $item->id }}">
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
            @if($isAdded)
                <flux:input wire:model.live.blur="items.{{$item->id}}.short_name" :disabled="$isDeleted"/>
            @else
                {{-- F2 (OP#581): the Titelnummer of a base (parent-plan) item is immutable in an
                     amendment — the numbering scheme belongs to the parent plan. --}}
                <flux:input value="{{ $item->short_name }}" readonly variant="filled"/>
            @endif
        </div>
        <div class="col-span-3 my-2 flex items-center gap-2">
            <flux:input class="flex-1" wire:model.live.blur="items.{{$item->id}}.name" :disabled="$isDeleted"
                        :class:input="$nameChange !== null ? 'ring-2 ring-amber-400 dark:ring-amber-500' : ''"
                        :title="$nameChange !== null ? __('budget-plan.amendment.field-was', ['value' => $nameChange['from']]) : null"/>
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
                <x-money-input class="my-2 w-full" wire:model.live.blur="items.{{$item->id}}.value" :disabled="$isDeleted"
                               :class:input="$valueChange !== null ? 'text-right ring-2 ring-amber-400 dark:ring-amber-500' : 'text-right'"
                               :title="$valueChange !== null ? __('budget-plan.amendment.field-was', ['value' => \Cknow\Money\Money::EUR((int) $valueChange['from'])->format()]) : null"/>
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
                    :level="$level + 1"
                    :last-item="[...$lastItem, $loop->last]"
                />
            @endforeach
        </div>
    @endif
</div>
