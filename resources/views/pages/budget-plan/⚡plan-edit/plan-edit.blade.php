<div class="space-y-10">
    <div>
        <flux:heading size="lg">{{ __('budget-plan.edit.headline') }}</flux:heading>
        <flux:text class="mt-2">{{ __('budget-plan.edit.sub') }}</flux:text>
    </div>

    <flux:fieldset class="max-w-3xl">
        <div class="grid grid-cols-2 gap-x-16 gap-y-6">
            <flux:input wire:model.live.blur="organization" :label="__('budget-plan.edit.organization')" type="text" :description="__('budget-plan.edit.organization-sub')"/>
            <flux:select
                variant="listbox"
                wire:model.live="fiscal_year_id"
                :label="__('budget-plan.fiscal-year')"
                :description="__('budget-plan.edit.fiscal-year-sub')"
                :placeholder="__('budget-plan.edit.no-fiscal-year')"
                clearable
            >
                @foreach($fiscal_years as $fiscal_year)
                    <flux:select.option wire:key="fiscal-{{$fiscal_year->id}}" value="{{$fiscal_year->id}}">{{ $fiscal_year->start_date->format('d.m.y') }} - {{ $fiscal_year->end_date->format('d.m.y') }}</flux:select.option>
                @endforeach

                {{-- pinned create action at the bottom of the dropdown --}}
                <flux:select.option.create wire:click="createFiscalYear">{{ __('budget-plan.edit.add-fiscal-year') }}</flux:select.option.create>
            </flux:select>
            <flux:input wire:model.live.blur="resolution_date" badge="Optional" :label="__('budget-plan.edit.resolution-date')" type="date"/>
            <flux:input wire:model.live.blur="approval_date" badge="Optional" :label="__('budget-plan.edit.approval-date')" type="date"/>
        </div>
    </flux:fieldset>
    <flux:tab.group class="max-w-7xl">
        <flux:tabs >
            <flux:tab name="in">
                {{ __('budget-plan.edit.tab-headline.in') }}
                <flux:badge color="indigo" size="sm">{{ $in_total->format() }}</flux:badge>
            </flux:tab>
            <flux:tab name="out">
                {{ __('budget-plan.edit.tab-headline.out') }}
                <flux:badge color="indigo" size="sm">{{ $out_total->format() }}</flux:badge>
            </flux:tab>
        </flux:tabs>
        @foreach(\App\Models\Enums\BudgetType::cases() as $budgetType)
            <flux:tab.panel :name="$budgetType->slug()">
                <flux:fieldset>
                    {{-- first track shrinks to the drag-handle width; tracks 2-8 stay equal so all col-start/col-span/subgrid references below are unchanged --}}
                    <div class="grid grid-cols-[auto_repeat(7,minmax(0,1fr))] px-4">
                        <div class="col-span-8 grid grid-cols-subgrid gap-4">
                            <div class="col-start-2 flex items-center gap-2">
                                {{ __('budget-plan.budget-shortname') }}
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle" />
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        {{ __('budget-plan.edit.table.headline.shortname-hint') }}
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <div class="col-start-4 flex items-center gap-2">
                                {{ __('budget-plan.budget-longname') }}
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle" />
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        {{ __('budget-plan.edit.table.headline.name-hint') }}
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <div class="col-start-6 col-span-2 flex justify-end items-center gap-2">
                                {{ __('budget-plan.budget-value') }}
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="subtle"/>
                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        {{ __('budget-plan.edit.table.headline.value-hint') }}
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                        </div>
                        <div class="col-span-8 grid grid-cols-subgrid gap-x-4"
                             wire:sort="sort">
                            @foreach($root_items[$budgetType->slug()] as $id)
                                <x-budgetplan.item-group-edit :item="$all_items[$id]" :wire:key="$id" :category="$budgetType->slug()"/>
                            @endforeach
                        </div>
                        <div class="col-start-1 col-span-4 grid grid-cols-subgrid gap-4">
                            {{-- span all 4 sub-columns so the button text doesn't force the auto col-1 (handle) track wider --}}
                            <flux:button class="col-span-4 justify-self-start" icon="plus" wire:click="addGroup({{ $budgetType->value }})" variant="subtle">{{ __('budget-plan.edit.new-group') }}</flux:button>
                        </div>
                    </div>
                </flux:fieldset>
            </flux:tab.panel>
        @endforeach
    </flux:tab.group>
    <div class="flex gap-2">
        <flux:button wire:click="save" variant="primary">{{ __('budget-plan.edit.save') }}</flux:button>
        @if (\App\Models\Setting::get('tax.active', false))
            <flux:button wire:click="addTaxTitles" icon="receipt-percent" variant="subtle">{{ __('budget-plan.edit.add-tax-titles') }}</flux:button>
        @endif
    </div>

    {{-- mount picker: turn the chosen item into a reference to another plan's in/out --}}
    <flux:modal name="mount-plan" class="md:w-96">
        {{-- entangle so the select + confirm button react client-side (instant), no round-trip --}}
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('budget-plan.edit.mount-heading') }}</flux:heading>
            <flux:text>{{ __('budget-plan.edit.mount-sub') }}</flux:text>

            {{-- skeleton while the cycle-filtered candidate list loads --}}
            <div wire:loading.flex wire:target="openMountPicker" class="flex-col gap-3">
                <div class="h-10 w-full animate-pulse rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="h-9 w-28 self-end animate-pulse rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
            </div>

            <div wire:loading.remove wire:target="openMountPicker" class="space-y-4">
                <flux:select wire:model.live="mount_plan_id" :placeholder="__('budget-plan.edit.mount-pick')">
                        <flux:select.option value="null">{{ __('None') }}</flux:select.option>
                    @foreach($mount_candidates as $candidate)
                        <flux:select.option wire:key="mount-cand-{{ $candidate['id'] }}" value="{{ $candidate['id'] }}">{{ $candidate['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('budget-plan.fiscal-year.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="convertToMount" variant="primary" :disabled="! $mount_plan_id">
                        {{ __('budget-plan.edit.mount-confirm') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
