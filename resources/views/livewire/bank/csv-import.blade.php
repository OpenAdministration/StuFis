<div class="mt-8 sm:mx-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('konto.manual-headline') }}</h1>
            <p class="mt-2 text-sm text-gray-700">{{ __('konto.manual-headline-sub') }}</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <a href="#" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                <x-heroicon-o-plus class="-ml-0.5 mr-2 h-4 w-4"/>
                {{ __('konto.csv-button-new-konto') }}
            </a>
        </div>
    </div>
    <div>
        <div class="py-3">
            <label for="account_id" class="block text-sm font-medium leading-6 text-gray-900">{{ __('konto.csv-label-choose-konto') }}</label>
            <select wire:model.live="account_id"
                    class="my-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
            >
                <option value="" selected></option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
            </select>
            <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
                @isset($latestTransaction)
                    <div class="flex justify-between gap-x-4 py-3">
                        <dt class="text-gray-500">{{ __('konto.csv-latest-saldo') }}:</dt>
                        <dd class="text-gray-700">
                            {{ $latestTransaction->saldo }} €
                        </dd>
                    </div>
                    <div class="flex justify-between gap-x-4 py-3">
                        <dt class="text-gray-500">{{ __('konto.csv-latest-date') }}:</dt>
                        <dd class="text-gray-700">
                            {{ $latestTransaction->date }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-x-4 py-3">
                        <dt class="text-gray-500">{{ __('konto.csv-latest-zweck') }}:</dt>
                        <dd class="text-gray-700">
                            {{ $latestTransaction->zweck }}
                        </dd>
                    </div>
                @else
                    @isset($account_id)
                        <div class="flex justify-between gap-x-4 py-3">
                            <dt class="text-gray-700 center">{{ __('konto.csv-no-transaction') }}</dt>
                        </div>
                    @endisset
                @endisset
            </dl>
        </div>

        @isset($account_id)
            <div class="flex items-center justify-center w-full">
                <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">{{ __('Click to upload') }}</span>{{ __(' or drag and drop') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('CSV from Bank') }}</p>
                    </div>
                    <input id="dropzone-file" type="file" wire:model.live="csv" />
                </label>
            </div>
            @error('csv') <span class="error">{{ $message }}</span> @enderror
        @endif
    </div>
    @if($data->count() > 0)
        <div>
            <x-grid-list>
                @foreach($labels as $attr => $label)
                    <x-grid-list.item-card wire:key="{{ $attr }}">
                        <div>
                            <div class="flex justify-between">
                                <label for="mapping.{{ $attr }}" class="block text-sm font-medium leading-6 text-gray-900">
                                    {{ __($label) }}:
                                </label>
                                <span class="text-sm leading-6 text-gray-500" id="email-optional">{{ __("konto.hint.transaction.$attr") }}</span>
                            </div>
                            <div class="mt-2">
                                <select wire:model.live="mapping.{{ $attr }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        aria-describedby="email-optional">
                                    <option value=""></option>
                                    @foreach($header as $csv_column_id => $value)
                                        <option value="{{ $csv_column_id }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <x-slot:rows>
                            @isset($firstNewTransaction[$mapping[$attr]])
                                <x-grid-list.item-card.row label="konto.csv-preview-first">
                                    {{ $firstNewTransaction[$mapping[$attr]] }}
                                </x-grid-list.item-card.row>
                            @endisset
                            @isset($lastNewTransaction[$mapping[$attr]])
                                <x-grid-list.item-card.row label="konto.csv-preview-last">
                                    {{ $lastNewTransaction[$mapping[$attr]] }}
                                </x-grid-list.item-card.row>
                            @endisset
                        </x-slot:rows>
                    </x-grid-list.item-card>
                @endforeach
            </x-grid-list>
            <div class="py-4">
                <button wire:click="save" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto" id="submit-assign">
                    {{ __('konto.manual-button-assign') }}
                </button>
            </div>
            <br>
            <div class="col-md-6 offset-md-3">
                @if(session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
