<div class="space-y-6 p-6 max-w-4xl">
    {{-- Header with Budget Plan Selector --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('dashboard.page_titles.projects') }}</flux:heading>
            <flux:text class="mt-1">
                {{ $this->currentBudgetPlan?->label() }}
            </flux:text>
        </div>
        <flux:select wire:model.live="hhpId" variant="listbox" class="w-full sm:w-64">
            @foreach($this->budgetPlans as $plan)
                <flux:select.option value="{{ $plan->id }}">
                    {{ $plan->label() }}
                    @if($plan->state)
                        <span class="text-zinc-400">({{ $plan->state }})</span>
                    @endif
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Tabs --}}
    <flux:tabs wire:model.live="tab">
        <flux:tab name="mygremium" icon="home">
            {{ __('dashboard.tabs.my_committees') }}
        </flux:tab>
        <flux:tab name="allgremium" icon="globe-alt">
            {{ __('dashboard.tabs.all_committees') }}
        </flux:tab>
        <flux:tab name="open-projects" icon="document-text">
            {{ __('dashboard.tabs.open_projects') }}
        </flux:tab>
    </flux:tabs>

    {{-- Content --}}
    @if($tab === 'mygremium' && empty($this->userCommittees))
        <flux:callout icon="exclamation-triangle" color="amber">
            <flux:callout.heading>{{ __('dashboard.alerts.no_committee_title') }}</flux:callout.heading>
            <flux:callout.text>{{ __('dashboard.alerts.no_committee_message') }}</flux:callout.text>
        </flux:callout>
    @elseif(empty($this->projectsByCommittee))
        @if($tab === 'open-projects')
            <flux:callout icon="check-circle" color="green">
                <flux:callout.heading>{{ __('dashboard.alerts.no_open_projects_title') }}</flux:callout.heading>
                <flux:callout.text>{{ __('dashboard.alerts.no_open_projects_message') }}</flux:callout.text>
            </flux:callout>
        @else
            <flux:callout icon="information-circle" color="amber">
                <flux:callout.heading>{{ __('dashboard.alerts.no_projects_title') }}</flux:callout.heading>
                <flux:callout.text>{{ __('dashboard.alerts.no_projects_message') }}</flux:callout.text>
                <x-slot name="actions">
                    <flux:button href="{{ route('legacy.new-projekt') }}" variant="primary" size="sm">
                        {{ __('dashboard.alerts.create_new_project') }}
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif
    @else
        <div class="space-y-8">
            @foreach($this->projectsByCommittee as $committee => $projects)
                @if(count($projects) > 0)
                    <section>
                        {{-- Committee Header --}}
                        <div class="flex items-center gap-3 mb-4">
                            <flux:heading size="lg">
                                {{ $committee ?: __('dashboard.unassigned_projects') }}
                            </flux:heading>
                            <flux:badge color="zinc">{{ count($projects) }}</flux:badge>
                        </div>

                        {{-- Projects Grid --}}
                        <div class="grid gap-3">
                            @foreach($projects as $project)
                                @php
                                    $year = $project['createdat']->format('y');
                                    $projectId = $project['id'];
                                    $expenses = $this->expensesByProjectId[$projectId] ?? [];
                                    $hasExpenses = count($expenses) > 0;
                                    $stateLabel = is_object($project['state']) ? $project['state']->label() : $project['state'];
                                @endphp

                                <div
                                    x-data="{ expanded: false }"
                                    class="group rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden transition-shadow hover:shadow-md"
                                >
                                    {{-- Project Header Row --}}
                                    <div class="flex items-center gap-4 p-4">
                                        {{-- Project ID & Name --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3">
                                                <flux:link
                                                    href="{{ route('legacy.projekt', $projectId) }}"
                                                    class="shrink-0 font-mono text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"
                                                >
                                                    IP-{{ $year }}-{{ $projectId }}
                                                </flux:link>
                                                <flux:badge size="sm" color="sky">{{ $stateLabel }}</flux:badge>
                                            </div>
                                            <h3 class="mt-1 font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                                {{ $project['name'] }}
                                            </h3>
                                        </div>

                                        {{-- Budget Info --}}
                                        <div class="hidden sm:flex items-center gap-6 text-sm shrink-0">
                                            <div class="text-right">
                                                <div class="text-zinc-500">{{ __('dashboard.table.expenses') }}</div>
                                                <div class="font-medium tabular-nums">
                                                    {{ number_format($project['total_ausgaben'] ?? 0, 2, ',', '.') }} €
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-zinc-500">{{ __('dashboard.table.income') }}</div>
                                                <div class="font-medium tabular-nums">
                                                    {{ number_format($project['total_einnahmen'] ?? 0, 2, ',', '.') }} €
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Expand Button (only if has expenses) --}}
                                        @if($hasExpenses)
                                            <button
                                                type="button"
                                                x-on:click="expanded = !expanded"
                                                class="p-2 rounded-lg text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 dark:hover:text-zinc-300 transition-colors"
                                            >
                                                <flux:icon
                                                    name="chevron-down"
                                                    class="size-5 transition-transform duration-200"
                                                    x-bind:class="{ 'rotate-180': expanded }"
                                                />
                                            </button>
                                        @else
                                            <div class="w-9"></div>
                                        @endif
                                    </div>

                                    {{-- Mobile Budget Info --}}
                                    <div class="sm:hidden px-4 pb-4 flex gap-4 text-sm">
                                        <div>
                                            <span class="text-zinc-500">{{ __('dashboard.table.expenses') }}:</span>
                                            <span class="font-medium tabular-nums">{{ number_format($project['total_ausgaben'] ?? 0, 2, ',', '.') }} €</span>
                                        </div>
                                        <div>
                                            <span class="text-zinc-500">{{ __('dashboard.table.income') }}:</span>
                                            <span class="font-medium tabular-nums">{{ number_format($project['total_einnahmen'] ?? 0, 2, ',', '.') }} €</span>
                                        </div>
                                    </div>

                                    {{-- Expenses Panel (Expandable) --}}
                                    @if($hasExpenses)
                                        <div
                                            x-show="expanded"
                                            x-collapse
                                            x-cloak
                                        >
                                            <div class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 p-4">
                                                <flux:heading size="sm" class="mb-3">{{ __('dashboard.expenses_heading') }}</flux:heading>

                                                {{-- Expenses Table --}}
                                                <div class="overflow-x-auto -mx-4 px-4">
                                                    <flux:table>
                                                        <flux:table.columns>
                                                            <flux:table.column>{{ __('dashboard.table.name') }}</flux:table.column>
                                                            <flux:table.column class="hidden md:table-cell">{{ __('dashboard.table.recipient') }}</flux:table.column>
                                                            <flux:table.column class="text-right">{{ __('dashboard.table.income') }}</flux:table.column>
                                                            <flux:table.column class="text-right">{{ __('dashboard.table.expenses') }}</flux:table.column>
                                                            <flux:table.column>{{ __('dashboard.table.status') }}</flux:table.column>
                                                        </flux:table.columns>
                                                        <flux:table.rows>
                                                            @php
                                                                $sumSubmittedIn = 0;
                                                                $sumSubmittedOut = 0;
                                                                $sumPaidIn = 0;
                                                                $sumPaidOut = 0;
                                                            @endphp
                                                            @foreach($expenses as $expense)
                                                                @php
                                                                    $expenseIn = collect($expense['receipts'] ?? [])->flatMap(fn($r) => $r['posts'] ?? [])->sum('einnahmen');
                                                                    $expenseOut = collect($expense['receipts'] ?? [])->flatMap(fn($r) => $r['posts'] ?? [])->sum('ausgaben');

                                                                    $state = $expense['state'];
                                                                    if (str_starts_with($state, 'booked') || str_starts_with($state, 'instructed')) {
                                                                        $sumPaidIn += $expenseIn;
                                                                        $sumPaidOut += $expenseOut;
                                                                    }
                                                                    if (!str_starts_with($state, 'revocation') && !str_starts_with($state, 'draft')) {
                                                                        $sumSubmittedIn += $expenseIn;
                                                                        $sumSubmittedOut += $expenseOut;
                                                                    }

                                                                    $stateKey = explode(';', $state)[0];
                                                                    $stateColor = match($stateKey) {
                                                                        'draft' => 'zinc',
                                                                        'wip' => 'amber',
                                                                        'ok' => 'green',
                                                                        'instructed' => 'sky',
                                                                        'booked' => 'emerald',
                                                                        'revocation' => 'red',
                                                                        default => 'zinc'
                                                                    };
                                                                @endphp
                                                                <flux:table.row>
                                                                    <flux:table.cell>
                                                                        <flux:link href="{{ route('legacy.expense', [$projectId, $expense['id']]) }}" class="font-medium">
                                                                            A{{ $expense['id'] }}
                                                                            @if($expense['name_suffix'])
                                                                                - {{ $expense['name_suffix'] }}
                                                                            @endif
                                                                        </flux:link>
                                                                    </flux:table.cell>
                                                                    <flux:table.cell class="hidden md:table-cell text-zinc-500">
                                                                        {{ $expense['zahlung_name'] ?: '—' }}
                                                                    </flux:table.cell>
                                                                    <flux:table.cell class="text-right tabular-nums">
                                                                        {{ number_format($expenseIn, 2, ',', '.') }} €
                                                                    </flux:table.cell>
                                                                    <flux:table.cell class="text-right tabular-nums">
                                                                        {{ number_format($expenseOut, 2, ',', '.') }} €
                                                                    </flux:table.cell>
                                                                    <flux:table.cell>
                                                                        <flux:badge size="sm" :color="$stateColor">
                                                                            {{ __('dashboard.expense_states.' . $stateKey) }}
                                                                        </flux:badge>
                                                                    </flux:table.cell>
                                                                </flux:table.row>
                                                            @endforeach
                                                        </flux:table.rows>
                                                    </flux:table>
                                                </div>

                                                {{-- Summary Cards --}}
                                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <div class="rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-3">
                                                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-2">
                                                            {{ __('dashboard.summary.submitted') }}
                                                        </div>
                                                        <div class="flex items-baseline gap-4 text-sm">
                                                            <div>
                                                                <span class="text-zinc-500">↓</span>
                                                                <span class="tabular-nums font-medium">{{ number_format($sumSubmittedIn, 2, ',', '.') }} €</span>
                                                            </div>
                                                            <div>
                                                                <span class="text-zinc-500">↑</span>
                                                                <span class="tabular-nums font-medium">{{ number_format($sumSubmittedOut, 2, ',', '.') }} €</span>
                                                            </div>
                                                            <div class="ml-auto">
                                                                <span class="text-zinc-500">Δ</span>
                                                                <span class="tabular-nums font-semibold {{ ($sumSubmittedOut - $sumSubmittedIn) >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                                    {{ number_format($sumSubmittedOut - $sumSubmittedIn, 2, ',', '.') }} €
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-3">
                                                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-2">
                                                            {{ __('dashboard.summary.paid') }}
                                                        </div>
                                                        <div class="flex items-baseline gap-4 text-sm">
                                                            <div>
                                                                <span class="text-zinc-500">↓</span>
                                                                <span class="tabular-nums font-medium">{{ number_format($sumPaidIn, 2, ',', '.') }} €</span>
                                                            </div>
                                                            <div>
                                                                <span class="text-zinc-500">↑</span>
                                                                <span class="tabular-nums font-medium">{{ number_format($sumPaidOut, 2, ',', '.') }} €</span>
                                                            </div>
                                                            <div class="ml-auto">
                                                                <span class="text-zinc-500">Δ</span>
                                                                <span class="tabular-nums font-semibold {{ ($sumPaidOut - $sumPaidIn) >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                                    {{ number_format($sumPaidOut - $sumPaidIn, 2, ',', '.') }} €
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif
</div>
