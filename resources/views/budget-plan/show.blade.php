<x-layout>
    BudgetPlan.show ->
    <flux:button :href="route('budget-plan.edit', ['plan_id' => $plan->id])">Edit</flux:button>
</x-layout>
