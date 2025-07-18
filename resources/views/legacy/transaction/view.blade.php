<x-layout class="px-16 py-12">
    <div class="px-4 sm:px-0 max-w-4xl">
        <flux:heading level="1" size="xl">{{ __('konto.transaction.headline') }}</flux:heading>
    </div>
    <div class="mt-6 max-w-4xl">
        <dl class="grid grid-cols-1 sm:grid-cols-2">
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __('konto.new.name-headline') }}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{$account->name}}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __('konto.transaction.id') }}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{$account->short}}{{ $transaction->id }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __('konto.label.transaction.empf_name') }}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ $transaction->empf_name }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __('konto.label.transaction.type') }}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ $transaction->type }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __( 'konto.label.transaction.empf_iban' )}}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ iban_to_human_format($transaction->empf_iban) }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __( 'konto.label.transaction.empf_bic' )}}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ $transaction->empf_bic }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __( 'konto.label.transaction.date' )}}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ $transaction->date->format('d.m.Y') }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __( 'konto.label.transaction.valuta' )}}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ $transaction->valuta->format('d.m.Y') }}</dd>
            </div>

            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __( 'konto.label.transaction.value' )}}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ money_format($transaction->value) }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-1 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __( 'konto.label.transaction.saldo' )}}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ money_format($transaction->saldo) }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-2 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __( 'konto.label.transaction.zweck' )}}</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">{{ $transaction->zweck }}</dd>
            </div>
            <div class="border-t border-gray-100 px-4 py-6 sm:col-span-2 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">{{ __( 'konto.transaction.bookings' )}}</dt>
                <dd class="mt-2 text-sm text-gray-700">
                    @if($bookings->isEmpty())
                        {{ __( 'konto.transaction.bookings.empty' )}}
                    @else
                        <ul role="list" class="divide-y divide-gray-100 rounded-md border border-gray-200">
                            @foreach($bookings as $booking)
                                @php $receipt = $receiptsFromBookings[$booking->id]; @endphp
                                <li class="flex items-center justify-between py-4 pr-5 pl-4 text-sm/6">
                                    <div class="flex w-0 flex-1 items-center">
                                        <div class="ml-4 flex min-w-0 flex-1 gap-2">
                                            <span class="shrink-0 text-gray-400">{{ $booking->id }}</span>
                                            <span class="truncate text-gray-900">{{ $booking->comment }}</span>
                                            <flux:link variant="subtle" :href="route('legacy.budget-item', ['titel_id' => $booking->budgetItem->id])"  class="shrink-0">{{ $booking->budgetItem->titel_nr }} {{ $booking->budgetItem->titel_name }}</flux:link>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex shrink-0 space-x-4">
                                        <span>{{ money_format($booking->value) }}</span>
                                        <span class="text-gray-200" aria-hidden="true">|</span>
                                        <flux:link :href="route('legacy.expense', ['auslage_id' => $receipt->auslagen_id])">A{{ $receipt->auslagen_id }}</flux:link>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endempty
                </dd>
            </div>
        </dl>
    </div>
</x-layout>
