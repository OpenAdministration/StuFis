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
                @unless($plan->isAmendment())
                    {{-- OP#588: read-only now that capture moved into the state-change modal —
                         resolution_date/approval_date have no editor of their own any more, so
                         this header is their only display surface (mirrors the fiscal-year span
                         above rather than inventing a new layout) --}}
                    <span>{{ __('budget-plan.edit.resolution-date') }}: {{ $plan->resolution_date?->format('d.m.Y') ?? '—' }}</span>
                    <span>{{ __('budget-plan.edit.approval-date') }}: {{ $plan->approval_date?->format('d.m.Y') ?? '—' }}</span>
                @endunless
            </span>
        </x-slot:subHeadline>
        <x-slot:button>
            <flux:dropdown>
                <flux:button icon:trailing="chevron-down"
                             variant="primary">{{ __('budget-plan.view.actions') }}</flux:button>
                <flux:menu>
                    @if(! $plan->isAmendment())
                        {{-- F7 (OP#581): moved here from a standalone header button so it lives
                             alongside the plan's other actions --}}
                        @if($can_create_amendment)
                            <flux:menu.item icon="document-plus" wire:click="createAmendment">
                                {{ __('budget-plan.amendment.create') }}
                            </flux:menu.item>
                        @else
                            <flux:tooltip :content="__('budget-plan.amendment.create-not-possible')">
                                <div>
                                    <flux:menu.item icon="document-plus" disabled>{{ __('budget-plan.amendment.create') }}</flux:menu.item>
                                </div>
                            </flux:tooltip>
                        @endif
                        <flux:menu.separator/>
                    @endif
                    @if($plan->isAmendment())
                        {{-- an amendment is only editable through its dedicated editor, and only while Draft --}}
                        @if($plan->state instanceof \App\States\BudgetPlan\Draft)
                            <flux:menu.item icon="pencil"
                                            :href="route('budget-plan.amendment.edit', [$plan->parent_plan_id, $plan->id])" wire:navigate>{{ __('budget-plan.view.edit') }}</flux:menu.item>
                        @else
                            <flux:menu.item icon="pencil" disabled>{{ __('budget-plan.view.edit') }}</flux:menu.item>
                        @endif
                    @elseif($plan->isEditable())
                        <flux:menu.item icon="pencil"
                                        :href="route('budget-plan.edit', $plan->id)">{{ __('budget-plan.view.edit') }}</flux:menu.item>
                    @else
                        {{-- F8 (OP#581): frozen from Approved onward — the plan is meant to be a
                             stable, agreed-upon document past that point --}}
                        <flux:tooltip :content="__('budget-plan.view.edit-not-possible', ['state' => $plan->state->label()])">
                            <div>
                                <flux:menu.item icon="pencil" disabled>{{ __('budget-plan.view.edit') }}</flux:menu.item>
                            </div>
                        </flux:tooltip>
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
                    {{-- downloads must be real navigations (file responses), so no wire:navigate here --}}
                    <flux:menu.submenu icon="arrow-down-tray" :heading="__('budget-plan.view.export')">
                        <flux:menu.item icon="table-cells" :href="route('budget-plan.export', [$plan->id, 'xlsx'])">
                            {{ __('budget-plan.view.export.excel') }}
                        </flux:menu.item>
                        <flux:menu.item icon="table-cells" :href="route('budget-plan.export', [$plan->id, 'ods'])">
                            {{ __('budget-plan.view.export.ods') }}
                        </flux:menu.item>
                        @if(\App\Models\Setting::get('datev', false))
                            @can('download', \App\Exports\Datev\DatevExport::class)
                                <flux:menu.item icon="banknotes" :href="route('datev.export', ['hhpId' => $plan->id])">
                                    {{ __('budget-plan.view.export.datev') }}
                                </flux:menu.item>
                            @endcan
                        @endif
                    </flux:menu.submenu>
                    @can('admin', \App\Models\User::class)
                        <flux:menu.separator/>
                        {{-- a native window.confirm() would be the only non-Flux dialog left in the
                             app (and is unstyleable), so this goes through a flux:modal like every
                             other confirmation — same pattern as ⚡show-project's delete-modal --}}
                        <flux:menu.item icon="trash" variant="danger"
                                        x-on:click="$flux.modal('delete-plan-modal').show()">
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
                                {{ $amendment->label() }} — {{ $amendment->state->label() }}
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

    @if($plan->isAmendment())
        <div class="max-w-3xl space-y-6">
            {{-- OP#588: read-only now that capture moved into the state-change modal — nothing
                 here is editable any more, so (unlike the pre-OP#588 version) this is always the
                 same unconditional display, never a $dates_editable-gated input. --}}
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500">{{ __('budget-plan.edit.approval-date') }}</dt>
                    <dd>{{ $plan->approval_date?->format('d.m.Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">{{ __('budget-plan.amendment.effective-date') }}</dt>
                    <dd>{{ $plan->effective_date?->format('d.m.Y') ?? '—' }}</dd>
                </div>
            </dl>

            @if(filled($plan->justification))
                <div>
                    <flux:heading size="sm">{{ __('budget-plan.amendment.justification') }}</flux:heading>
                    <flux:text class="mt-1 whitespace-pre-line">{{ $plan->justification }}</flux:text>
                </div>
            @endif

            <x-budgetplan.amendment-delta-summary :summary="$delta_summary"/>

            <div>
                <flux:heading size="sm">{{ __('budget-plan.amendment.diff-heading') }}</flux:heading>
                @if($amendment_changes->isEmpty())
                    <flux:text class="mt-2 italic text-gray-500">{{ __('budget-plan.amendment.no-changes-yet') }}</flux:text>
                @else
                    <div class="mt-2 divide-y divide-gray-200 rounded-lg outline-1 outline-black/5">
                        @foreach($amendment_changes as $change)
                            @php $changedItem = $change->budgetItem; @endphp
                            <div class="p-4 space-y-2">
                                <div class="flex items-center gap-2">
                                    <flux:badge size="sm" :color="match($change->action) {
                                        'add' => 'green', 'delete' => 'red', default => 'amber',
                                    }">{{ __('budget-plan.amendment.change.'.$change->action) }}</flux:badge>
                                    <span class="font-medium">{{ $changedItem?->short_name }} — {{ $changedItem?->name }}</span>
                                </div>
                                @if($change->action === 'modify' && filled($change->diff))
                                    <ul class="text-sm text-gray-600 list-disc list-inside">
                                        @foreach($change->diff as $field => $pair)
                                            <li>
                                                {{ __('budget-plan.amendment.field.'.$field) }}:
                                                @if($field === 'value')
                                                    {{ \Cknow\Money\Money::EUR((int) $pair['from'])->format() }}
                                                    → {{ \Cknow\Money\Money::EUR((int) $pair['to'])->format() }}
                                                @else
                                                    „{{ $pair['from'] }}“ → „{{ $pair['to'] }}“
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if(filled($change->reason))
                                    <flux:text class="text-sm">
                                        <span class="font-medium">{{ __('budget-plan.amendment.reason-label') }}:</span>
                                        <span class="italic">{{ $change->reason }}</span>
                                    </flux:text>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @else
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
    @endif

    {{-- state-change modal: lists only the transitions allowed from the current state --}}
    <flux:modal name="state-modal" class="min-w-96">
        <div>
            <flux:heading size="lg">{{ __('budget-plan.view.state-modal.heading') }}</flux:heading>
            @php $transitions = $plan->state->transitionableStateInstances(); @endphp
            @if(count($transitions) === 0)
                <flux:text class="mt-4">{{ __('budget-plan.view.state-modal.no-transitions') }}</flux:text>
            @else
                {{-- .live so $newState is synced server-side as soon as it's picked — the optional
                     date field(s) below react to it (see targetState()), with no JS involved --}}
                <flux:select wire:model.live="newState" variant="listbox" class="mt-4"
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
        @php
            // OP#588: which of the three optional meta dates (if any) belong to the currently
            // selected target state — never shown together, never on a backward step (see
            // changeState()'s matching $isForwardStep gate), and effective_date stays
            // amendment-only throughout.
            $targetState = $this->targetState();
            $isForwardStep = $targetState && $plan->state->advancesTo($targetState);
        @endphp
        @if($isForwardStep && $targetState instanceof \App\States\BudgetPlan\Resolved)
            <div class="mt-4">
                <flux:input wire:model="resolution_date" type="date" badge="Optional"
                            :label="__('budget-plan.edit.resolution-date')"/>
            </div>
        @elseif($isForwardStep && $targetState instanceof \App\States\BudgetPlan\Approved)
            <div class="mt-4 grid grid-cols-1 gap-4 @if($plan->isAmendment()) sm:grid-cols-2 @endif">
                <flux:input wire:model="approval_date" type="date" badge="Optional"
                            :label="__('budget-plan.edit.approval-date')"/>
                @if($plan->isAmendment())
                    <flux:input wire:model="effective_date" type="date" badge="Optional"
                                :label="__('budget-plan.amendment.effective-date')"
                                :description="__('budget-plan.amendment.effective-date-hint')"/>
                @endif
            </div>
        @elseif($isForwardStep && $targetState instanceof \App\States\BudgetPlan\Active && $plan->isAmendment() && $plan->effective_date === null)
            <div class="mt-4">
                <flux:input wire:model="effective_date" type="date" badge="Optional"
                            :label="__('budget-plan.amendment.effective-date')"
                            :description="__('budget-plan.amendment.effective-date-hint')"/>
            </div>
        @endif
        @if ($errors->has('newState'))
            {{-- looped (not a single @error), since a business-rule failure (OP#584) can name
                 more than one offending Titel at once --}}
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 my-4">
                <ul class="list-disc list-inside text-red-600 text-sm space-y-1">
                    @foreach ($errors->get('newState') as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
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

    @can('admin', \App\Models\User::class)
        {{-- F5 (OP#589): same checklist pattern as ⚡show-project's delete-modal — a condition row
             per requirement, Confirm disabled until every one holds, rather than a bare
             heading + Cancel/Confirm. --}}
        <flux:modal name="delete-plan-modal" class="md:w-[32rem]">
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <x-fas-triangle-exclamation class="h-6 w-6 text-red-600"/>
                    </div>
                    <h3 class="text-lg leading-6 font-bold text-gray-900">{{ __('budget-plan.view.delete-modal.heading') }}</h3>
                </div>

                <div class="space-y-2">
                    <p class="text-sm text-gray-500">{{ __('budget-plan.view.delete-modal.intro') }}</p>
                    <ul class="text-sm text-gray-500 space-y-1">
                        <li class="flex items-start gap-2">
                            @if($user_can_delete_plan)
                                <x-fas-circle-check class="w-4 h-4 mt-0.5 shrink-0 fill-green-600"/>
                            @else
                                <x-fas-circle-xmark class="w-4 h-4 mt-0.5 shrink-0 fill-red-600"/>
                            @endif
                            <span>{{ __('budget-plan.view.delete-modal.conditions.admin') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            @if($plan_deletable)
                                <x-fas-circle-check class="w-4 h-4 mt-0.5 shrink-0 fill-green-600"/>
                            @else
                                <x-fas-circle-xmark class="w-4 h-4 mt-0.5 shrink-0 fill-red-600"/>
                            @endif
                            <span>{{ __('budget-plan.view.delete-modal.conditions.editable-state', ['state' => $plan->state->label()]) }}</span>
                        </li>
                    </ul>
                    <p class="text-sm text-gray-500">{{ __('budget-plan.view.delete-confirm') }}</p>
                </div>

                <div class="flex gap-3">
                    <flux:spacer/>
                    <flux:button x-on:click="$flux.modal('delete-plan-modal').close()" variant="ghost">
                        {{ __('budget-plan.view.delete-modal.cancel') }}
                    </flux:button>
                    <flux:button wire:click="deletePlan" variant="danger" :disabled="! ($user_can_delete_plan && $plan_deletable)">
                        {{ __('budget-plan.view.delete-modal.confirm') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan

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
