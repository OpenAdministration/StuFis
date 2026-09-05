@props([
    /** @var array{income: \Cknow\Money\Money, expense: \Cknow\Money\Money, saldo: \Cknow\Money\Money} */
    'summary',
])

<div>
    <flux:heading size="sm">{{ __('budget-plan.amendment.delta-heading') }}</flux:heading>
    <dl class="mt-2 grid grid-cols-1 divide-gray-200 overflow-hidden rounded-lg bg-white shadow-sm sm:grid-cols-3 sm:divide-x sm:divide-y-0 outline-1 outline-black/5">
        <div class="px-4 py-4">
            <dt class="text-sm text-gray-500">{{ __('budget-plan.amendment.delta-income') }}</dt>
            <dd class="mt-1 text-lg font-semibold text-indigo-600">{{ $summary['income']->format() }}</dd>
        </div>
        <div class="px-4 py-4">
            <dt class="text-sm text-gray-500">{{ __('budget-plan.amendment.delta-expense') }}</dt>
            <dd class="mt-1 text-lg font-semibold text-indigo-600">{{ $summary['expense']->format() }}</dd>
        </div>
        <div class="px-4 py-4">
            <dt class="text-sm text-gray-500">{{ __('budget-plan.amendment.delta-saldo') }}</dt>
            <dd @class([
                'mt-1 text-lg font-semibold',
                'text-red-600' => $summary['saldo']->isNegative(),
                'text-green-600' => ! $summary['saldo']->isNegative(),
            ])>{{ $summary['saldo']->format() }}</dd>
        </div>
    </dl>
</div>
