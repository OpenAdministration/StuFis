<div>
    <x-intro>
        {{ $id ? __('budget-plan.fiscal-year.edit.headline') : __('budget-plan.fiscal-year.create.headline') }}
        <x-slot:subHeadline>
            {{ __('budget-plan.fiscal-year.edit.sub') }}
        </x-slot:subHeadline>
    </x-intro>

    <form wire:submit="save" class="max-w-sm space-y-4">
        <flux:input wire:model="start_date" :label="__('budget-plan.fiscal-year.start')" type="date"/>
        <flux:input wire:model="end_date" :label="__('budget-plan.fiscal-year.end')" type="date"/>

        <div class="mt-6 flex gap-2">
            <flux:button variant="primary" type="submit">
                {{ __('budget-plan.fiscal-year.save') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('budget-plan.index')" wire:navigate>
                {{ __('budget-plan.fiscal-year.cancel') }}
            </flux:button>
        </div>
    </form>
</div>
