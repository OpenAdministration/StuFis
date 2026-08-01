<div class="space-y-10">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">
                {{ __('budget-plan.amendment.edit-headline') }} · {{ $parentPlan->label() }} · {{ $amendment->label() }}
                <flux:badge color="zinc" size="sm">{{ __('budget-plan.amendment.badge') }}</flux:badge>
            </flux:heading>
            <flux:text class="mt-2">{{ __('budget-plan.amendment.edit-sub') }}</flux:text>
        </div>
        {{-- F4 (OP#581): explicit back affordance to the amendment's own view (mirrors edit-project's
             back button pattern) — the editor otherwise has no way back except the browser button --}}
        <flux:button :href="route('budget-plan.view', $amendment->id)" wire:navigate variant="outline"
                     icon="arrow-left">{{ __('budget-plan.amendment.back-to-view') }}</flux:button>
    </div>

    <flux:tab.group class="max-w-7xl">
        <flux:tabs>
            <flux:tab name="titles">{{ __('budget-plan.amendment.tab-titles') }}</flux:tab>
            <flux:tab name="reasons">{{ __('budget-plan.amendment.tab-reasons') }}</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="titles">
            <flux:tab.group class="max-w-7xl mt-6">
                <flux:tabs>
                    <flux:tab name="in">{{ __('budget-plan.edit.tab-headline.in') }}</flux:tab>
                    <flux:tab name="out">{{ __('budget-plan.edit.tab-headline.out') }}</flux:tab>
                </flux:tabs>
                @foreach(\App\Models\Enums\BudgetType::cases() as $budgetType)
                    <flux:tab.panel :name="$budgetType->slug()">
                        <flux:fieldset>
                            <div class="grid grid-cols-[auto_repeat(7,minmax(0,1fr))] px-4">
                                <div class="col-span-8 grid grid-cols-subgrid gap-4">
                                    <div class="col-start-2">{{ __('budget-plan.budget-shortname') }}</div>
                                    <div class="col-start-4">{{ __('budget-plan.budget-longname') }}</div>
                                    <div class="col-start-6 col-span-2 text-right">{{ __('budget-plan.budget-value') }}</div>
                                </div>
                                <div class="col-span-8 grid grid-cols-subgrid gap-x-4" wire:sort="sort">
                                    @foreach($root_items[$budgetType->slug()] as $item)
                                        <x-budgetplan.item-group-amendment
                                            :item="$item"
                                            :values="$values"
                                            :changes="$changes"
                                            :amendment-id="$amendment->id"
                                        />
                                    @endforeach
                                </div>
                                <div class="col-start-1 col-span-4 grid grid-cols-subgrid gap-4">
                                    <div class="col-span-4 flex gap-2 justify-self-start">
                                        <flux:button icon="plus" wire:click="addGroup({{ $budgetType->value }})" variant="subtle">{{ __('budget-plan.edit.new-group') }}</flux:button>
                                        <flux:button icon="plus" wire:click="addRootBudget({{ $budgetType->value }})" variant="subtle">{{ __('budget-plan.edit.new-budget') }}</flux:button>
                                    </div>
                                </div>
                            </div>
                        </flux:fieldset>
                    </flux:tab.panel>
                @endforeach
            </flux:tab.group>
        </flux:tab.panel>

        <flux:tab.panel name="reasons">
            <div class="max-w-3xl space-y-6 mt-6">
                <flux:input wire:model.live.blur="name" badge="Optional"
                            :label="__('budget-plan.amendment.name')"
                            :description="__('budget-plan.amendment.name-sub')"/>
                <flux:textarea wire:model.live.blur="justification"
                               :label="__('budget-plan.amendment.justification')"
                               :description="__('budget-plan.amendment.justification-sub')"
                               rows="4"/>

                <x-budgetplan.amendment-delta-summary :summary="$delta_summary"/>

                @if($changes->isEmpty())
                    <flux:text class="italic text-gray-500">{{ __('budget-plan.amendment.no-changes-yet') }}</flux:text>
                @else
                    <div class="divide-y divide-gray-200">
                        @foreach($changes as $change)
                            @php $changedItem = \App\Models\BudgetItem::find($change->budget_item_id); @endphp
                            <div class="py-4 space-y-2">
                                <div class="flex items-center gap-2">
                                    <flux:badge size="sm" :color="match($change->action) {
                                        'add' => 'green', 'delete' => 'red', default => 'amber',
                                    }">{{ __('budget-plan.amendment.change.'.$change->action) }}</flux:badge>
                                    <span class="font-medium">{{ $changedItem?->short_name }} — {{ $changedItem?->name }}</span>
                                </div>
                                @if($change->action === 'modify' && filled($change->diff))
                                    <ul class="text-sm text-gray-600 list-disc list-inside">
                                        @foreach($change->diff as $field => $pair)
                                            <li>
                                                {{ __('budget-plan.amendment.field.'.$field) }}:
                                                @if($field === 'value')
                                                    {{ \Cknow\Money\Money::EUR((int) $pair['from'])->format() }}
                                                    → {{ \Cknow\Money\Money::EUR((int) $pair['to'])->format() }}
                                                @else
                                                    „{{ $pair['from'] }}“ → „{{ $pair['to'] }}“
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <flux:textarea wire:model.live.blur="reasonInputs.{{ $change->id }}"
                                               :placeholder="__('budget-plan.amendment.reason-placeholder')"
                                               rows="2"/>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</div>
