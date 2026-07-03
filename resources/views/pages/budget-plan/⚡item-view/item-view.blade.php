<div class="space-y-6">
    <x-intro>
        <x-slot:headline>{{ $item->short_name }} · {{ $item->name }}</x-slot:headline>
        <x-slot:subHeadline>
            <span class="inline-flex flex-wrap items-center gap-2">
                <flux:link :href="route('budget-plan.view', $plan->id)" wire:navigate>{{ __('budget-plan.view.headline') }} · {{ $plan->label() }}</flux:link>
            </span>
        </x-slot:subHeadline>
    </x-intro>

    <div class="max-w-(--breakpoint-lg)">
        <dl class="mt-5 grid grid-cols-1 divide-gray-200 overflow-hidden rounded-lg bg-white shadow-sm md:grid-cols-3 md:divide-x md:divide-y-0">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">{{ __('budget-plan.view.col.planned') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-indigo-600">{{ $planned->format() }}</dd>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">{{ __('budget-plan.view.col.booked') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-indigo-600">{{ $booked->format() }}</dd>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-base font-normal text-gray-900">{{ __('budget-plan.view.col.committed') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-indigo-600">{{ $committed->format() }}</dd>
            </div>
        </dl>
    </div>

    {{-- Bookings against this item --}}
    <div>
        <flux:heading size="lg" class="mb-4">{{ __('budget-plan.item.bookings') }}</flux:heading>

        @if($rows->isEmpty())
            <flux:text>{{ __('budget-plan.item.no-bookings') }}</flux:text>
        @else
            <div class="-mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-sm outline-1 outline-black/5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-white">
                            <tr class="text-sm font-medium text-gray-900">
                                <th scope="col" class="py-3.5 pr-3 pl-4 text-left sm:pl-6">{{ __('budget-plan.item.col.date') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left">{{ __('budget-plan.item.col.payment') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left">{{ __('budget-plan.item.col.reference') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-right sm:pr-6">{{ __('budget-plan.item.col.amount') }}</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($rows as $row)
                                    <tr class="odd:bg-gray-50 text-sm whitespace-nowrap text-gray-700">
                                        <td class="py-4 pr-3 pl-4 text-left sm:pl-6">{{ $row['timestamp'] }}</td>
                                        <td class="px-3 py-4 text-left">
                                            @if($row['transaction'])
                                                <flux:link :href="route('bank-account.transaction', [$row['transaction']->konto_id, $row['transaction']->id])">
                                                    {{ $row['transaction']->name }}
                                                </flux:link>
                                            @else
                                                <span class="text-gray-300">–</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 text-left">
                                            @if($row['project'])
                                                <flux:link :href="route('project.show', $row['project']->id)" wire:navigate>{{ $row['project']->name }}</flux:link>
                                            @elseif(filled($row['comment']))
                                                {{ $row['comment'] }}
                                            @else
                                                <span class="text-gray-300">–</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 text-right sm:pr-6">{{ $row['amount']->format() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
