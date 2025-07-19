<div class="space-y-10 p-8">
    <div>
        <flux:heading size="lg">{{ __('budget-plan.edit.headline') }}</flux:heading>
        <flux:text class="mt-2">{{ __('budget-plan.edit.sub') }}</flux:text>
    </div>

    <flux:fieldset class="max-w-3xl">
        <div class="grid grid-cols-2 gap-x-16 gap-y-6">
            <flux:input wire:model.blur="organization" :label="__('budget-plan.edit.organization')" type="text" :description="__('budget-plan.edit.organization-sub')"/>
            <flux:select wire:model.change="fiscal_year_id" :label="__('budget-plan.edit.fiscal-year')" :description="__('budget-plan.edit.fiscal-year-sub')">
                <flux:select.option wire:key="fiscal-0">None</flux:select.option>
                @foreach($fiscal_years as $fiscal_year)
                    <flux:select.option wire:key="fiscal-{{$fiscal_year->id}}" value="{{$fiscal_year->id}}">{{ $fiscal_year->start_date->format('d.m.y') }} - {{ $fiscal_year->end_date->format('d.m.y') }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.blur="resolution_date" badge="Optional" :label="__('budget-plan.edit.resolution-date')" type="date"/>
            <flux:input wire:model.blur="approval_date" badge="Optional" :label="__('budget-plan.edit.approval-date')" type="date"/>
        </div>
    </flux:fieldset>
    <flux:tab.group class="max-w-7xl">
        <flux:tabs >
            <flux:tab name="in">
                {{ __('budget-plan.edit.tab-headline.in') }}
                <flux:badge color="indigo" size="sm">100.000,01€</flux:badge>
            </flux:tab>
            <flux:tab name="out">
                {{ __('budget-plan.edit.tab-headline.out') }}
                <flux:badge color="indigo" size="sm">100.400,01€</flux:badge>
            </flux:tab>
        </flux:tabs>
        @foreach(\App\Models\Enums\BudgetType::cases() as $budgetType)
            <flux:tab.panel :name="$budgetType->slug()">
                <flux:fieldset>
                    <div class="grid grid-cols-8">
                        <div class="col-span-8 grid grid-cols-subgrid gap-4">
                            <div class="col-start-2 flex items-center gap-2">
                                {{ __('budget-plan.edit.table.headline.shortname') }}
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle" />
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        {{ __('budget-plan.edit.table.headline.shortname-hint') }}
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <div class="col-start-4 flex items-center gap-2">
                                {{ __('budget-plan.edit.table.headline.name') }}
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle" />
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        {{ __('budget-plan.edit.table.headline.name-hint') }}
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <div class="col-start-6 flex items-center gap-2">
                                {{ __('budget-plan.edit.table.headline.value') }}
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle" />
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        {{ __('budget-plan.edit.table.headline.value-hint') }}
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                        </div>
                        <div class="col-span-8 grid grid-cols-subgrid gap-x-4"
                             x-sort="$wire.sort($item, $position)">
                            @foreach($root_items[$budgetType->slug()] as $id)
                                <x-item-group :item="$all_items[$id]" :wire:key="$id" :category="$budgetType->slug()"/>
                            @endforeach
                        </div>
                        <div class="col-start-1 col-span-4 grid grid-cols-subgrid gap-4">
                            <flux:button wire:click="addGroup(1)" variant="subtle">+ New Group</flux:button>
                        </div>
                    </div>
                </flux:fieldset>
            </flux:tab.panel>
        @endforeach
    </flux:tab.group>
    <div>
        <flux:button wire:click="save" variant="primary">{{ __('budget-plan.edit.save') }}</flux:button>
        <flux:text variant="subtle">
            Last saved yesterday
        </flux:text>
    </div>
    @dump($all_items)
    @dump($items)
</div>
