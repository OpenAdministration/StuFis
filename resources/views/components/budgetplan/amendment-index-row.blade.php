@php use App\Models\BudgetPlan; @endphp
@props([
    /** @var BudgetPlan */
    'amendment',
])

{{-- One amendment row of the plan index, indented under its parent plan. No type badge: label()
     already reads "Nachtrag vom …" for an unnamed amendment, and a badge next to it would only say
     the same thing twice. Shared by the per-fiscal-year and the orphaned-plans block, which list
     amendments identically. --}}
<flux:table.row>
    <flux:table.cell class="ps-14!">
        <flux:link :href="route('budget-plan.view', $amendment->id)">{{ $amendment->label() }}</flux:link>
    </flux:table.cell>
    <flux:table.cell>
        <flux:badge :color="$amendment->state?->color() ?? 'green'" size="sm" inset="top bottom">
            {{ $amendment->state?->label() }}
        </flux:badge>
    </flux:table.cell>
</flux:table.row>
