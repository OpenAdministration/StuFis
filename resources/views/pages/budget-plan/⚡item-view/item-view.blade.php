<div>
    <x-intro>
        <x-slot:headline>{{ $item->short_name }} · {{ $item->name }}</x-slot:headline>
        <x-slot:subHeadline>{{ __('budget-plan.item.subtitle') }}</x-slot:subHeadline>
    </x-intro>

    {{-- one or more amendments currently touch this title — parallel amendments are allowed
         (OP#581), so several rows in different states (pending draft, already-applied active, ...)
         can show at once. Reuses the same action badge / label fallback / reason idiom as the
         amendment's own diff view (⚡plan-view) rather than duplicating it under a new name. --}}
    @if($amendment_changes->isNotEmpty())
        <flux:callout color="zinc" icon="document-text" inline class="mt-6">
            <flux:callout.heading>{{ __('budget-plan.item.amendment-hint.heading') }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($amendment_changes as $change)
                        @php $amendment = $change->amendmentPlan; @endphp
                        <li>
                            <flux:badge size="sm" :color="$change->action->color()">{{ $change->action->label() }}</flux:badge>
                            <flux:link :href="route('budget-plan.view', $amendment->id)" wire:navigate>
                                {{ $amendment->label() }}
                            </flux:link>
                            <flux:badge size="sm" :color="$amendment->state->color()">{{ $amendment->state->label() }}</flux:badge>
                            @if(filled($change->reason))
                                <flux:text class="text-sm italic">— {{ $change->reason }}</flux:text>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

    {{-- meter, dropped well below the page header --}}
    <div class="mt-10">
        {{-- how the Plan budget is consumed (Gebucht ⊆ Beschlossen ⊆ Plan) --}}
        <x-budgetplan.consumption-meter :planned="$planned" :booked="$booked" :committed="$committed"/>
    </div>

    {{-- Detail tables in tabs so a long booking/project list doesn't crowd the other. Wrapped in a
         real element for the horizontal inset: flux:tab.group renders as display:contents, so
         padding set on it never applies. --}}
    <div class="mt-8 px-4">
    <flux:tab.group>
        <flux:tabs wire:model.live="tab">
            <flux:tab name="bookings">{{ __('budget-plan.item.tab.bookings') }}</flux:tab>
            <flux:tab name="committed">{{ __('budget-plan.item.tab.committed') }}</flux:tab>
        </flux:tabs>

        {{-- Buchungen (IST-Gebucht) --}}
        <flux:tab.panel name="bookings" class="pt-4">
            @if($rows->isEmpty())
                <div class="flex flex-col items-center gap-3 py-12 text-center">
                    <flux:icon.inbox class="size-8 text-zinc-300 dark:text-zinc-600"/>
                    <flux:text variant="subtle">{{ __('budget-plan.item.no-bookings') }}</flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$bookingSort === 'id'" :direction="$bookingDir" wire:click="sortBookings('id')">{{ __('budget-plan.item.col.booking-id') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$bookingSort === 'date'" :direction="$bookingDir" wire:click="sortBookings('date')">{{ __('budget-plan.item.col.booking-date') }}</flux:table.column>
                        <flux:table.column>{{ __('budget-plan.item.col.payment-id') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$bookingSort === 'project'" :direction="$bookingDir" wire:click="sortBookings('project')">{{ __('budget-plan.item.col.project') }}</flux:table.column>
                        <flux:table.column>{{ __('budget-plan.item.col.receipt') }}</flux:table.column>
                        <flux:table.column>{{ __('budget-plan.item.col.post-ref') }}</flux:table.column>
                        <flux:table.column align="end" sortable :sorted="$bookingSort === 'amount'" :direction="$bookingDir" wire:click="sortBookings('amount')">{{ __('budget-plan.item.col.amount') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($rows as $row)
                            <flux:table.row :key="$row['id']">
                                <flux:table.cell variant="strong" class="tabular-nums">
                                    {{-- links to this HHP's legacy booking history table --}}
                                    <flux:link :href="route('legacy.booking.history', ['hhp_id' => $plan->id])">B{{ $row['id'] }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap tabular-nums">{{ $row['date']?->format('d.m.Y') ?? '—' }}</flux:table.cell>
                                <flux:table.cell class="tabular-nums">
                                    @if($row['transaction'])
                                        <flux:link :href="route('bank-account.transaction', [$row['transaction']->konto_id, $row['transaction']->id])" :title="$row['transaction']->date?->format('d.m.Y')">
                                            {{ $row['transaction']->name }}
                                        </flux:link>
                                    @else
                                        <flux:text variant="subtle">–</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($row['project'])
                                        <flux:link :href="route('project.show', $row['project']->id)" wire:navigate>P{{ $row['project']->id }}</flux:link>
                                    @else
                                        <flux:text variant="subtle">–</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($row['expense'])
                                        <flux:link :href="route('legacy.expense', $row['expense']->id)">A{{ $row['expense']->id }} · {{ $row['expense']->name_suffix }}</flux:link>
                                    @else
                                        <flux:text variant="subtle">–</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="tabular-nums">
                                    {{ $row['post'] ? '#'.$row['post']->id : '–' }}
                                </flux:table.cell>
                                <flux:table.cell align="end" variant="strong" class="tabular-nums">{{ $row['amount']->format() }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:tab.panel>

        {{-- Beschlossen (IST-Beschlossen): the projects/postings that make up the committed figure.
             The counting figure is emphasised per row — open projects commit their planned posting
             (Betrag Posten), terminated ones only what has been billed (Abgerechnete Summe) — so the
             emphasised column always reconciles to the meter's Beschlossen total. --}}
        <flux:tab.panel name="committed" class="pt-4">
            @if($committedRows->isEmpty())
                <div class="flex flex-col items-center gap-3 py-12 text-center">
                    <flux:icon.banknotes class="size-8 text-zinc-300 dark:text-zinc-600"/>
                    <flux:text variant="subtle">{{ __('budget-plan.item.no-committed') }}</flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$committedSort === 'project'" :direction="$committedDir" wire:click="sortCommitted('project')">{{ __('budget-plan.item.col.project') }}</flux:table.column>
                        <flux:table.column>{{ __('budget-plan.item.col.post') }}</flux:table.column>
                        <flux:table.column align="end" sortable :sorted="$committedSort === 'planned'" :direction="$committedDir" wire:click="sortCommitted('planned')">{{ __('budget-plan.item.col.post-amount') }}</flux:table.column>
                        <flux:table.column align="end" sortable :sorted="$committedSort === 'billed'" :direction="$committedDir" wire:click="sortCommitted('billed')">{{ __('budget-plan.item.col.billed') }}</flux:table.column>
                        <flux:table.column align="center" sortable :sorted="$committedSort === 'state'" :direction="$committedDir" wire:click="sortCommitted('state')">{{ __('budget-plan.item.col.project-state') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($committedRows as $row)
                            <flux:table.row :key="$row['posten_id']">
                                <flux:table.cell variant="strong">
                                    <flux:link :href="route('project.show', $row['project_id'])" wire:navigate>P{{ $row['project_id'] }} · {{ $row['project_name'] }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if(filled($row['posten_name']))
                                        {{ $row['posten_name'] }}
                                    @else
                                        <flux:text variant="subtle">–</flux:text>
                                    @endif
                                </flux:table.cell>
                                {{-- bold every non-zero figure that feeds the meter: an open project's
                                     planned amount is committed (yellow), and any already-billed part of it
                                     shows as booked (purple); a terminated project counts only its billed
                                     sum (its planned/beschlossen no longer applies once abgeschlossen) --}}
                                <flux:table.cell align="end" :variant="($row['is_open'] && ! $row['planned']->isZero()) ? 'strong' : null" class="tabular-nums">{{ $row['planned']->format() }}</flux:table.cell>
                                <flux:table.cell align="end" :variant="(! $row['billed']->isZero()) ? 'strong' : null" class="tabular-nums">{{ $row['billed']->format() }}</flux:table.cell>
                                <flux:table.cell align="center">
                                    <flux:badge size="sm" :color="$row['is_open'] ? 'sky' : 'zinc'">{{ __('project.stateNames.'.$row['project_state']) }}</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:tab.panel>
    </flux:tab.group>
    </div>
</div>
