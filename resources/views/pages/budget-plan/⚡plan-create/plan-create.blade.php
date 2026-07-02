<div>
    <x-intro>
        <x-slot:headline>{{ __('budget-plan.create.headline') }}</x-slot:headline>
        <x-slot:subHeadline>{{ __('budget-plan.create.sub') }}</x-slot:subHeadline>
    </x-intro>

    <form wire:submit="save" class="max-w-lg space-y-6">
        <flux:radio.group wire:model.live="starting_point" :label="__('budget-plan.create.starting-point')">
            <flux:radio value="template" :label="__('budget-plan.create.start-template')" :description="__('budget-plan.create.start-template-sub')"/>
            <flux:radio value="clone" :label="__('budget-plan.create.start-clone')" :description="__('budget-plan.create.start-clone-sub')"/>
        </flux:radio.group>

        @if($starting_point === 'clone')
            <flux:select
                variant="listbox"
                wire:model.live="source_plan_id"
                :label="__('budget-plan.create.source-plan')"
                :placeholder="__('budget-plan.create.source-plan-pick')"
                searchable
            >
                @foreach($source_plans as $candidate)
                    <flux:select.option wire:key="source-{{ $candidate->id }}" :value="$candidate->id">
                        {{ $candidate->label() }}@if($candidate->fiscalYear) · {{ $candidate->fiscalYear->label() }}@endif
                    </flux:select.option>
                @endforeach
            </flux:select>

            @if($mounted_plans->isNotEmpty())
                <flux:fieldset>
                    <flux:legend>{{ __('budget-plan.create.mounts-heading') }}</flux:legend>
                    <flux:description>{{ __('budget-plan.create.mounts-sub') }}</flux:description>
                    <div class="mt-3 space-y-3">
                        @foreach($mounted_plans as $sub)
                            <div wire:key="mount-choice-{{ $sub->id }}" class="flex flex-wrap items-center justify-between gap-3">
                                <flux:text>{{ $sub->label() }}</flux:text>
                                <flux:radio.group wire:model="mountChoices.{{ $sub->id }}" variant="segmented" size="sm">
                                    <flux:radio value="copy" :label="__('budget-plan.create.mount.copy')"/>
                                    <flux:radio value="drop" :label="__('budget-plan.create.mount.drop')"/>
                                </flux:radio.group>
                            </div>
                        @endforeach
                    </div>
                </flux:fieldset>
            @endif
        @endif

        <flux:select
            variant="listbox"
            wire:model.live="fiscal_year_id"
            :label="__('budget-plan.create.fiscal-year')"
            :description="__('budget-plan.edit.fiscal-year-sub')"
            :placeholder="__('budget-plan.edit.no-fiscal-year')"
            clearable
        >
            @foreach($fiscal_years as $fiscal_year)
                <flux:select.option wire:key="fiscal-{{ $fiscal_year->id }}" :value="$fiscal_year->id">{{ $fiscal_year->label() }}</flux:select.option>
            @endforeach

            <flux:select.option.create wire:click="createFiscalYear">{{ __('budget-plan.edit.add-fiscal-year') }}</flux:select.option.create>
        </flux:select>

        <flux:input
            wire:model="organization"
            :label="__('budget-plan.create.organization')"
            :description="__('budget-plan.edit.organization-sub')"
            type="text"
        />

        <div class="flex gap-2">
            <flux:button variant="primary" type="submit">{{ __('budget-plan.create.submit') }}</flux:button>
            <flux:button variant="ghost" :href="route('budget-plan.index')" wire:navigate>{{ __('budget-plan.create.cancel') }}</flux:button>
        </div>
    </form>
</div>
