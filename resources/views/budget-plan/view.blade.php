<x-layout>
    <div class="p-8 max-w-(--breakpoint-lg)">
        <x-intro>
            <x-slot:headline>head</x-slot:headline>
            <x-slot:subHeadline>sub</x-slot:subHeadline>
            <x-slot:button>
                <flux:dropdown>
                    <flux:button icon:trailing="chevron-down" variant="primary">Button</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="pencil" :href="route('budget-plan.edit', $plan->id)">edit</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </x-slot:button>
        </x-intro>

        <flux:tab.group class="max-w-7xl mt-6">
            <flux:tabs>
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
                <flux:tab.panel :name="$budgetType->slug()" class="pl-2">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>shortname</flux:table.column>
                            <flux:table.column>longname</flux:table.column>
                            <flux:table.column>value</flux:table.column>
                            <flux:table.column>booked</flux:table.column>
                            <flux:table.column>reserved</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($items[$budgetType->slug()] as $budgetItem)
                                <flux:table.row @class(['bg-zinc-200' => $budgetItem->is_group])>
                                    <flux:table.cell>
                                        {{ $budgetItem->short_name }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $budgetItem->name }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $budgetItem->value }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        tbd
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        tbd
                                    </flux:table.cell>
                                </flux:table.row>
                                @foreach($budgetItem->children as $child)

                                @endforeach
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:tab.panel>
            @endforeach
        </flux:tab.group>
    </div>
</x-layout>
