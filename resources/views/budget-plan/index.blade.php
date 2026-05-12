<x-layout>
    <div class="p-8 max-w-(--breakpoint-lg)">
        <x-intro>
            <x-slot:headline>{{ __('budget-plan.index.headline') }}</x-slot:headline>
            <x-slot:button>
                <flux:dropdown>
                    <flux:button icon:trailing="chevron-down" variant="primary">{{ __('budget-plan.index.button.new') }}</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="plus" :href="route('budget-plan.create')">{{ __('budget-plan.budget-plan') }}</flux:menu.item>
                        <flux:menu.item icon="plus" :href="route('fiscal-year.create')">{{ __('budget-plan.fiscal-year') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </x-slot:button>
        </x-intro>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('budget-plan.budget-plans') }}</flux:table.column>
                <flux:table.column>{{ __('budget-plan.index.table.state') }}</flux:table.column>
                <flux:table.column>{{ __('budget-plan.index.table.actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($years as $year)
                    <flux:table.row-headline>
                        {{ __('budget-plan.fiscal-year') }} {{ $year->start_date->format('M y')  }} to {{ $year->end_date->format('M y') }}
                        <flux:link :href="route('fiscal-year.edit', $year->id)"><x-fas-pencil class="size-3 mx-2"/></flux:link>
                    </flux:table.row-headline>
                    @foreach($year->budgetPlans as $plan)
                        <flux:table.row>
                            <flux:table.cell>
                                {{ __('budget-plan.fiscal-year') }} {{ $plan->id }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="green" size="sm" inset="top bottom">
                                    {{ $plan->state }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="inline-flex space-x-2">
                                <flux:link :href="route('budget-plan.edit', $plan->id)">
                                    <x-fas-pencil class="size-3.5"/>
                                </flux:link>
                                <flux:link :href="route('budget-plan.view', $plan->id)">
                                    <x-fas-eye class="size-3.5"/>
                                </flux:link>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                @endforeach
                @if($orphaned_plans->isNotEmpty())
                    <flux:table.row-headline>
                        Pläne ohhneee HHHHJ
                    </flux:table.row-headline>
                @endif
                @foreach($orphaned_plans as $plan)
                    <flux:table.row>
                        <flux:table.cell>
                            {{ __('budget-plan.plan?') }} {{ $plan->id }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="green" size="sm" inset="top bottom">
                                {{ $plan->state }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="inline-flex space-x-2">
                            <flux:link :href="route('budget-plan.edit', $plan->id)">
                                <x-fas-pencil class="size-3.5"/>
                            </flux:link>
                            <flux:link :href="route('budget-plan.view', $plan->id)">
                                <x-fas-eye class="size-3.5"/>
                            </flux:link>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</x-layout>
