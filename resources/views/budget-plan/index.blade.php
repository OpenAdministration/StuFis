<x-layout::app size="lg">
    <div>
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
            </flux:table.columns>

            <flux:table.rows>
                @foreach($years as $year)
                    <flux:table.row-headline>
                        {{ __('budget-plan.fiscal-year') }}: {{ $year->label() }}
                        <flux:link :href="route('fiscal-year.edit', $year->id)"><x-fas-pencil class="size-3 mx-2"/></flux:link>
                    </flux:table.row-headline>
                    @forelse($year->budgetPlans as $plan)
                        <flux:table.row>
                            <flux:table.cell class="ps-8!">
                                <flux:link :href="route('budget-plan.view', $plan->id)">{{ $plan->label() }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$plan->state?->color() ?? 'green'" size="sm" inset="top bottom">
                                    {{ $plan->state?->label() }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                        @foreach($plan->amendments as $amendment)
                            <flux:table.row>
                                <flux:table.cell class="ps-14!">
                                    <flux:badge color="zinc" size="sm">{{ __('budget-plan.amendment.badge') }}</flux:badge>
                                    <flux:link :href="route('budget-plan.view', $amendment->id)">{{ $amendment->label() }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$amendment->state?->color() ?? 'green'" size="sm" inset="top bottom">
                                        {{ $amendment->state?->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="2" class="ps-8! text-gray-500 italic">
                                {{ __('budget-plan.index.no-plans') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                @endforeach

                @if($orphaned_plans->isNotEmpty())
                    <flux:table.row-headline>
                        {{ __('budget-plan.index.orphaned-plans') }}
                    </flux:table.row-headline>
                    @foreach($orphaned_plans as $plan)
                        <flux:table.row>
                            <flux:table.cell class="ps-8!">
                                <flux:link :href="route('budget-plan.view', $plan->id)">{{ $plan->organization ?: __('budget-plan.view.no-organization') }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$plan->state?->color() ?? 'green'" size="sm" inset="top bottom">
                                    {{ $plan->state?->label() }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                        @foreach($plan->amendments as $amendment)
                            <flux:table.row>
                                <flux:table.cell class="ps-14!">
                                    <flux:badge color="zinc" size="sm">{{ __('budget-plan.amendment.badge') }}</flux:badge>
                                    <flux:link :href="route('budget-plan.view', $amendment->id)">{{ $amendment->label() }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$amendment->state?->color() ?? 'green'" size="sm" inset="top bottom">
                                        {{ $amendment->state?->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    @endforeach
                @endif

                @if($years->isEmpty() && $orphaned_plans->isEmpty())
                    <flux:table.row>
                        <flux:table.cell colspan="2" class="text-center text-gray-500">
                            {{ __('budget-plan.index.no-plans') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endif
            </flux:table.rows>
        </flux:table>
    </div>
</x-layout::app>
