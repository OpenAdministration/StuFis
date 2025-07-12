<x-layout>
    <div class="px-4 sm:px-0">
        <flux:heading level="1" size="xl">{{ __('konto.transaction.headline') }}</flux:heading>
    </div>
    <div class="mt-6">
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
                <dt class="text-sm/6 font-medium text-gray-900">Attachments</dt>
                <dd class="mt-2 text-sm text-gray-900">
                    <ul role="list" class="divide-y divide-gray-100 rounded-md border border-gray-200">
                        <li class="flex items-center justify-between py-4 pr-5 pl-4 text-sm/6">
                            <div class="flex w-0 flex-1 items-center">
                                <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                    <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-4 flex min-w-0 flex-1 gap-2">
                                    <span class="truncate font-medium">resume_back_end_developer.pdf</span>
                                    <span class="shrink-0 text-gray-400">2.4mb</span>
                                </div>
                            </div>
                            <div class="ml-4 shrink-0">
                                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Download</a>
                            </div>
                        </li>
                        <li class="flex items-center justify-between py-4 pr-5 pl-4 text-sm/6">
                            <div class="flex w-0 flex-1 items-center">
                                <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                    <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-4 flex min-w-0 flex-1 gap-2">
                                    <span class="truncate font-medium">coverletter_back_end_developer.pdf</span>
                                    <span class="shrink-0 text-gray-400">4.5mb</span>
                                </div>
                            </div>
                            <div class="ml-4 shrink-0">
                                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Download</a>
                            </div>
                        </li>
                    </ul>
                </dd>
            </div>
        </dl>
    </div>
</x-layout>
