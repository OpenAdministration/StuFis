@php
    use App\Models\Enums\BudgetType;
    use Cknow\Money\Money;
@endphp

<x-layout>
    <div class="p-2 sm:p-8 space-y-6">
        <x-intro>
            <x-slot:headline>{{ __('budget-plan.view.headline') }}</x-slot:headline>
            <x-slot:subHeadline>
                {{ $plan->organization ?? __('budget-plan.view.no-organization') }}
                @if($plan->fiscalYear)
                    · {{ __('budget-plan.fiscal-year') }}: {{ $plan->fiscalYear->start_date->format('d.m.Y') }}
                    - {{ $plan->fiscalYear->end_date->format('d.m.Y') }}
                @endif
            </x-slot:subHeadline>
            <x-slot:button>
                <flux:dropdown>
                    <flux:button icon:trailing="chevron-down"
                                 variant="primary">{{ __('budget-plan.view.actions') }}</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="pencil"
                                        :href="route('budget-plan.edit', $plan->id)">{{ __('budget-plan.view.edit') }}</flux:menu.item>
                        <flux:menu.item
                            icon="document-duplicate">{{ __('budget-plan.view.duplicate') }}</flux:menu.item>
                        <flux:menu.item icon="printer">{{ __('budget-plan.view.print') }}</flux:menu.item>
                        <flux:menu.item icon="arrow-down-tray">{{ __('budget-plan.view.export') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </x-slot:button>
        </x-intro>

        <div class="max-w-(--breakpoint-lg)">
            <dl class="mt-5 grid grid-cols-1 divide-gray-200 overflow-hidden rounded-lg bg-white shadow-sm md:grid-cols-3 md:divide-x md:divide-y-0">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-base font-normal text-gray-900">Status</dt>
                    <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                        <div class="flex items-baseline text-2xl font-semibold text-indigo-600">
                            71,897
                            <span class="ml-2 text-sm font-medium text-gray-500">from 70,946</span>
                        </div>

                        <div class="inline-flex items-baseline rounded-full bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 md:mt-2 lg:mt-0">
                            <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="mr-0.5 -ml-1 size-5 shrink-0 self-center text-green-500">
                                <path d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" fill-rule="evenodd" />
                            </svg>
                            <span class="sr-only"> Increased by </span>
                            12%
                        </div>
                    </dd>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-base font-normal text-gray-900">Avg. Open Rate</dt>
                    <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                        <div class="flex items-baseline text-2xl font-semibold text-indigo-600">
                            58.16%
                            <span class="ml-2 text-sm font-medium text-gray-500">from 56.14%</span>
                        </div>

                        <div class="inline-flex items-baseline rounded-full bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 md:mt-2 lg:mt-0">
                            <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="mr-0.5 -ml-1 size-5 shrink-0 self-center text-green-500">
                                <path d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" fill-rule="evenodd" />
                            </svg>
                            <span class="sr-only"> Increased by </span>
                            2.02%
                        </div>
                    </dd>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-base font-normal text-gray-900">Avg. Click Rate</dt>
                    <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                        <div class="flex items-baseline text-2xl font-semibold text-indigo-600">
                            24.57%
                            <span class="ml-2 text-sm font-medium text-gray-500">from 28.62%</span>
                        </div>

                        <div class="inline-flex items-baseline rounded-full bg-red-100 px-2.5 py-0.5 text-sm font-medium text-red-800 md:mt-2 lg:mt-0">
                            <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="mr-0.5 -ml-1 size-5 shrink-0 self-center text-red-500">
                                <path d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd" fill-rule="evenodd" />
                            </svg>
                            <span class="sr-only"> Decreased by </span>
                            4.05%
                        </div>
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Budgetplan table --}}
        <flux:tab.group class="max-w-7xl">
            <flux:tabs class="sticky top-0 z-10 bg-gray-50 dark:bg-zinc-900">
                <flux:tab name="in">
                    {{ __('budget-plan.edit.tab-headline.in') }}
                </flux:tab>
                <flux:tab name="out">
                    {{ __('budget-plan.edit.tab-headline.out') }}
                </flux:tab>
            </flux:tabs>

            @foreach(BudgetType::cases() as $budgetType)
                <flux:tab.panel :name="$budgetType->slug()" class="pt-4">
                    <div class="sm:px-6">
                        <div class="mt-8 flow-root">
                            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                    <div class="overflow-hidden shadow-sm outline-1 outline-black/5 sm:rounded-lg">
                                        <table class="relative min-w-full divide-y divide-gray-300 overflow-y-auto">
                                            <thead class="bg-white">
                                            <tr class="even:bg-gray-50 text-sm font-medium text-gray-900">
                                                <th scope="col" class="py-3.5 pr-3 pl-4 text-left sm:pl-6">
                                                    Title
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left">
                                                    Name
                                                </th>
                                                <th scope="col" class="py-3.5">
                                                    {{-- Sigma column --}}
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-right">
                                                    Soll
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-right">
                                                    Ist
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-right sm:pr-6">
                                                    B
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                @foreach($items[$budgetType->slug()] as $item)
                                                    <x-budgetplan.view-row :item="$item"/>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{--
                    <div class="grid grid-cols-9 gap-4">
                        <div class="col-span-3">{{ __('budget-plan.budget-shortname') }}</div>
                        <div class="col-span-3">{{ __('budget-plan.budget-longname') }}</div>
                        <div class="text-right">{{ __('budget-plan.budget-value') }}</div>
                        <div class="text-right">{{ __('budget-plan.view.booked') }}</div>
                        <div class="text-right">{{ __('budget-plan.view.available') }}</div>

                        @foreach($items[$budgetType->slug()] as $budgetItem)
                            <x-budgetplan.item-group-view :item="$budgetItem" :level="0"/>
                        @endforeach

                    </div>
                    --}}
                </flux:tab.panel>
            @endforeach
        </flux:tab.group>

    </div>
</x-layout>
