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
            @if(! $plan->isAmendment())
                @if($can_create_amendment)
                    <flux:button icon="document-plus" variant="outline" wire:click="createAmendment">
                        {{ __('budget-plan.amendment.create') }}
                    </flux:button>
                @else
                    <flux:tooltip :content="__('budget-plan.amendment.create-not-possible')">
                        <div><flux:button icon="document-plus" variant="outline" disabled>
                                {{ __('budget-plan.amendment.create') }}
                            </flux:button></div>
                    </flux:tooltip>
                @endif
            @endif
            <flux:dropdown>
                <flux:button icon:trailing="chevron-down"
                             variant="primary">{{ __('budget-plan.view.actions') }}</flux:button>
                <flux:menu>
                    @if($plan->isAmendment())
                        {{-- an amendment is only editable through its dedicated editor, and only while Draft --}}
                        @if($plan->state instanceof \App\States\BudgetPlan\Draft)
                            <flux:menu.item icon="pencil"
                                            :href="route('budget-plan.amendment.edit', [$plan->parent_plan_id, $plan->id])" wire:navigate>{{ __('budget-plan.view.edit') }}</flux:menu.item>
                        @else
                            <flux:menu.item icon="pencil" disabled>{{ __('budget-plan.view.edit') }}</flux:menu.item>
                        @endif
                    @else
                        <flux:menu.item icon="pencil"
                                        :href="route('budget-plan.edit', $plan->id)">{{ __('budget-plan.view.edit') }}</flux:menu.item>
                    @endif
                    @can('update', $plan)
                        <flux:menu.item icon="arrow-path" x-on:click="$flux.modal('state-modal').show()">
                            {{ __('budget-plan.view.change-state') }}
                        </flux:menu.item>
                    @endcan
                    @if(! $plan->isAmendment())
                        @can('create', \App\Models\BudgetPlan::class)
                            {{-- duplication is "create from an existing plan": deep-link into the create flow with this plan preselected as the clone source --}}
                            <flux:menu.item icon="document-duplicate" :href="route('budget-plan.create', ['source' => $plan->id])" wire:navigate>{{ __('budget-plan.view.duplicate') }}</flux:menu.item>
                        @endcan
                    @endif
                    {{-- TODO: print not yet implemented — disabled until the print flow exists --}}
                    <flux:menu.item icon="printer" disabled>{{ __('budget-plan.view.print') }}</flux:menu.item>
                    {{-- downloads must be real navigations (file responses), so no wire:navigate here --}}
                    <flux:menu.submenu icon="arrow-down-tray" :heading="__('budget-plan.view.export')">
                        <flux:menu.item icon="table-cells" :href="route('budget-plan.export', [$plan->id, 'xlsx'])">
                            {{ __('budget-plan.view.export.excel') }}
                        </flux:menu.item>
                        <flux:menu.item icon="table-cells" :href="route('budget-plan.export', [$plan->id, 'ods'])">
                            {{ __('budget-plan.view.export.ods') }}
                        </flux:menu.item>
                    </flux:menu.submenu>
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

    @if($amendment_overdue)
        <flux:callout color="amber" icon="exclamation-triangle" inline>
            <flux:callout.heading>{{ __('budget-plan.amendment.overdue-heading') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('budget-plan.amendment.overdue-text', ['date' => $plan->effective_date->format('d.m.Y')]) }}
            </flux:callout.text>
        </flux:callout>
    @endif

    @if($open_amendments->isNotEmpty())
        <flux:callout color="zinc" icon="document-text" inline>
            <flux:callout.heading>{{ __('budget-plan.amendment.open-heading') }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($open_amendments as $amendment)
                        <li>
                            <flux:link :href="route('budget-plan.view', $amendment->id)" wire:navigate>
                                {{ __('budget-plan.amendment.badge') }} — {{ $amendment->state->label() }}
                            </flux:link>
                            @can('update', $amendment)
                                @if($amendment->state instanceof \App\States\BudgetPlan\Draft)
                                    ·
                                    <flux:link :href="route('budget-plan.amendment.edit', [$plan->id, $amendment->id])" wire:navigate>
                                        {{ __('budget-plan.amendment.continue-editing') }}
                                    </flux:link>
                                @endif
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

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
                <div
                    class="sm:px-6"
                    x-data="budgetCollapse('budget-collapse-{{ $plan->id }}-{{ $budgetType->slug() }}')"
                    data-group-ids="@json($items[$budgetType->slug()]->where('is_group', true)->pluck('id')->values())"
                >
                    {{-- collapse/expand every group at once; disabled (not hidden) when already in that state,
                         and absent entirely when the plan side has no groups to fold --}}
                    @if($items[$budgetType->slug()]->where('is_group', true)->isNotEmpty())
                        <div class="flex justify-end gap-2">
                            <flux:button size="xs" variant="subtle" icon="arrows-pointing-in"
                                         x-on:click="collapseAll()"
                                         x-bind:disabled="collapsed.length === allGroupIds.length">
                                {{ __('budget-plan.view.collapse-all') }}
                            </flux:button>
                            <flux:button size="xs" variant="subtle" icon="arrows-pointing-out"
                                         x-on:click="expandAll()"
                                         x-bind:disabled="collapsed.length === 0">
                                {{ __('budget-plan.view.expand-all') }}
                            </flux:button>
                        </div>
                    @endif
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
                                                <span class="inline-flex items-center justify-end gap-1">
                                                    {{ __('budget-plan.view.col.planned') }}
                                                    <flux:tooltip toggleable>
                                                        <flux:button icon="information-circle" size="xs" variant="subtle"/>
                                                        <flux:tooltip.content class="max-w-[20rem] space-y-2 text-center">
                                                            {{ __('budget-plan.view.col.planned-hint') }}
                                                        </flux:tooltip.content>
                                                    </flux:tooltip>
                                                </span>
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right">
                                                <span class="inline-flex items-center justify-end gap-1">
                                                    {{ __('budget-plan.view.col.booked') }}
                                                    <flux:tooltip toggleable>
                                                        <flux:button icon="information-circle" size="xs" variant="subtle"/>
                                                        <flux:tooltip.content class="max-w-[20rem] space-y-2 text-center">
                                                            {{ __('budget-plan.view.col.booked-hint') }}
                                                        </flux:tooltip.content>
                                                    </flux:tooltip>
                                                </span>
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right sm:pr-6">
                                                <span class="inline-flex items-center justify-end gap-1">
                                                    {{ __('budget-plan.view.col.committed') }}
                                                    <flux:tooltip toggleable>
                                                        <flux:button icon="information-circle" size="xs" variant="subtle"/>
                                                        <flux:tooltip.content class="max-w-[20rem] space-y-2 text-center">
                                                            {{ __('budget-plan.view.col.committed-hint') }}
                                                        </flux:tooltip.content>
                                                    </flux:tooltip>
                                                </span>
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

    {{-- Register the budgetCollapse Alpine component from a nonced inline <script> — the
         mechanism from the Livewire CSP docs' "Working around limitations" section. This
         keeps the component's JS inside its MFC rather than in the shared resources/js
         bundle. @cspNonce emits nonce="…" matching the CSP header (same scoped singleton),
         which is what makes an inline script pass our strict script-src. Full JS is allowed
         here (arrows/spreads) because a nonced <script> is a real script context, not an
         Alpine attribute expression — so the rich logic Alpine's CSP evaluator can't parse
         lives here and the blade keeps only CSP-safe expressions (bare refs, method calls).
         Register on alpine:init, or immediately if Alpine is already up — the latter covers
         wire:navigate INTO this page, where alpine:init already fired on the first page. --}}
    <script @cspNonce>
        const registerBudgetCollapse = () => {
            window.Alpine.data('budgetCollapse', (persistKey) => ({
                collapsed: window.Alpine.$persist([]).as(persistKey),
                allGroupIds: [],

                init() {
                    this.allGroupIds = JSON.parse(this.$el.dataset.groupIds || '[]');
                },

                toggle(id) {
                    this.collapsed = this.collapsed.includes(id)
                        ? this.collapsed.filter(existing => existing !== id)
                        : [...this.collapsed, id];
                },

                isHidden(row) {
                    const ancestors = JSON.parse(row.dataset.ancestorIds || '[]');
                    return ancestors.some(id => this.collapsed.includes(id));
                },

                collapseAll() { this.collapsed = [...this.allGroupIds]; },
                expandAll()   { this.collapsed = []; },
            }));
        };

        window.Alpine
            ? registerBudgetCollapse()
            : document.addEventListener('alpine:init', registerBudgetCollapse);
    </script>
</div>
