<div>
    <x-intro>
        {{ $id ? __('budget-plan.fiscal-year.edit.headline') : __('budget-plan.fiscal-year.create.headline') }}
        <x-slot:subHeadline>
            {{ __('budget-plan.fiscal-year.edit.sub') }}
        </x-slot:subHeadline>
    </x-intro>

    <form wire:submit="save" class="max-w-sm space-y-4">
        <flux:input wire:model.live="start_date" :label="__('budget-plan.fiscal-year.start')" type="date"/>
        <flux:input wire:model.live="end_date" :label="__('budget-plan.fiscal-year.end')" type="date"/>

        @if(count($this->gaps) > 0)
            <flux:callout color="amber" icon="exclamation-triangle" inline>
                <flux:callout.heading>{{ __('budget-plan.fiscal-year.gap-warning.heading') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('budget-plan.fiscal-year.gap-warning.text') }}
                    <ul class="mt-1 list-disc ps-4">
                        @foreach($this->gaps as $gap)
                            <li>{{ $gap['start']->format('d.m.Y') }} – {{ $gap['end']->format('d.m.Y') }}</li>
                        @endforeach
                    </ul>
                </flux:callout.text>
            </flux:callout>
        @endif

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
