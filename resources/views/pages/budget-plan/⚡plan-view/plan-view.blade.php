@php use App\Models\Enums\BudgetType; @endphp

<div class="space-y-6">
    <x-intro>
        <x-slot:headline>{{ __('budget-plan.view.headline') }} · {{ $plan->label() }}</x-slot:headline>
        <x-slot:subHeadline>
            <span class="inline-flex flex-wrap items-center gap-2">
                <flux:badge :color="$plan->state->color()" size="sm">{{ $plan->state->label() }}</flux:badge>
                @if($plan->fiscalYear)
                    <span>{{ __('budget-plan.fiscal-year') }}: {{ $plan->fiscalYear->label() }}</span>
                @endif
            </span>
        </x-slot:subHeadline>
        <x-slot:button>
            <flux:dropdown>
                <flux:button icon:trailing="chevron-down"
                             variant="primary">{{ __('budget-plan.view.actions') }}</flux:button>
                <flux:menu>
                    <flux:menu.item icon="pencil"
                                    :href="route('budget-plan.edit', $plan->id)">{{ __('budget-plan.view.edit') }}</flux:menu.item>
                    @can('update', $plan)
                        <flux:menu.item icon="arrow-path" x-on:click="$flux.modal('state-modal').show()">
                            {{ __('budget-plan.view.change-state') }}
                        </flux:menu.item>
                    @endcan
                    @can('create', \App\Models\BudgetPlan::class)
                        {{-- duplication is "create from an existing plan": deep-link into the create flow with this plan preselected as the clone source --}}
                        <flux:menu.item icon="document-duplicate" :href="route('budget-plan.create', ['source' => $plan->id])" wire:navigate>{{ __('budget-plan.view.duplicate') }}</flux:menu.item>
                    @endcan
                    {{-- TODO: not yet implemented — disabled until the print/export flows exist --}}
                    <flux:menu.item icon="printer" disabled>{{ __('budget-plan.view.print') }}</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" disabled>{{ __('budget-plan.view.export') }}</flux:menu.item>
                    @can('admin', \App\Models\User::class)
                        <flux:menu.separator/>
                        <flux:menu.item icon="trash" variant="danger"
                                        wire:click="deletePlan"
                                        wire:confirm="{{ __('budget-plan.view.delete-confirm') }}">
                            {{ __('budget-plan.view.delete') }}
                        </flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </x-slot:button>
    </x-intro>

    @php
        $income = $plan->incomeTotal();
        $expense = $plan->expenseTotal();
        $balance = $income->subtract($expense);
    @endphp
    <div class="max-w-(--breakpoint-lg)">
        <dl class="mt-5 grid grid-cols-1 divide-gray-200 overflow-hidden rounded-lg bg-white shadow-sm md:grid-cols-3 md:divide-x md:divide-y-0">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">{{ __('budget-plan.view.summary.income') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-indigo-600">{{ $income->format() }}</dd>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">{{ __('budget-plan.view.summary.expense') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-indigo-600">{{ $expense->format() }}</dd>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">{{ __('budget-plan.view.summary.balance') }}</dt>
                <dd @class([
                    'mt-1 text-2xl font-semibold',
                    'text-red-600' => $balance->isNegative(),
                    'text-green-600' => ! $balance->isNegative(),
                ])>{{ $balance->format() }}</dd>
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
                                                {{ __('budget-plan.budget-shortname') }}
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-left">
                                                {{ __('budget-plan.budget-longname') }}
                                            </th>
                                            <th scope="col" class="py-3.5">
                                                {{-- Sigma column --}}
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right">
                                                {{ __('budget-plan.view.col.planned') }}
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right">
                                                {{ __('budget-plan.view.col.booked') }}
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right sm:pr-6">
                                                {{ __('budget-plan.view.col.available') }}
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
            </flux:tab.panel>
        @endforeach
    </flux:tab.group>

    {{-- state-change modal: lists only the transitions allowed from the current state --}}
    <flux:modal name="state-modal" class="min-w-96">
        <div>
            <flux:heading size="lg">{{ __('budget-plan.view.state-modal.heading') }}</flux:heading>
            @php $transitions = $plan->state->transitionableStateInstances(); @endphp
            @if(count($transitions) === 0)
                <flux:text class="mt-4">{{ __('budget-plan.view.state-modal.no-transitions') }}</flux:text>
            @else
                <flux:select wire:model="newState" variant="listbox" class="mt-4"
                             placeholder="{{ __('budget-plan.view.state-modal.placeholder') }}">
                    @foreach($transitions as $state)
                        <flux:select.option :value="$state" :disabled="Auth::user()->cannot('transition-to', [$plan, $state])">
                            <div class="flex items-center gap-2">
                                <x-dynamic-component :component="$state->iconName()" class="size-4"/>
                                {{ $state->label() }}
                            </div>
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif
        </div>
        @error('newState')
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 my-4">
                <p class="text-red-600 text-sm">{{ $message }}</p>
            </div>
        @enderror
        <div class="mt-6 flex gap-3">
            <flux:spacer/>
            <flux:button x-on:click="$flux.modal('state-modal').close()" variant="ghost">
                {{ __('budget-plan.view.state-modal.cancel') }}
            </flux:button>
            <flux:button wire:click="changeState" variant="primary" :disabled="count($transitions) === 0">
                {{ __('budget-plan.view.state-modal.save') }}
            </flux:button>
        </div>
    </flux:modal>
</div>
