<div>
    <x-intro :headline="__('konto.manual-headline')" :sub-headline="__('konto.manual-headline-sub')"/>

    <div class="space-y-6">
        <div class="py-3">
            <flux:select wire:model.live.change="account_id" :label="__('konto.csv-label-choose-konto')">
                @foreach($accounts as $account)
                    <option wire:key="account-{{$account->id}}" value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
            </flux:select>
            @if($account_id !== "")
                <dl class="-my-3 divide-y divide-gray-100 py-4 text-sm leading-6">
                    @isset($latestTransaction)
                        <div wire:key="last-transaction" class="px-6 border-l-2 border-indigo-600">
                            <div class="flex justify-between gap-x-4 py-3">
                                <dt class="text-gray-500">{{ __('konto.csv-latest-saldo') }}:</dt>
                                <dd class="text-gray-700">{{ $this->formatDataView($latestTransaction->saldo, 'value') }}</dd>
                            </div>
                            <div class="flex justify-between gap-x-4 py-3">
                                <dt class="text-gray-500">{{ __('konto.csv-latest-date') }}:</dt>
                                <dd class="text-gray-700">
                                    {{ $latestTransaction->date->format('d.m.Y') }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-x-4 py-3">
                                <dt class="text-gray-500">{{ __('konto.csv-latest-zweck') }}:</dt>
                                <dd class="text-gray-700">
                                    {{ $latestTransaction->zweck }}
                                </dd>
                            </div>
                        </div>
                    @else
                        <div class="px-6 py-3 border-l-2 border-indigo-600" wire:key="no-transaction">
                            <dt class="text-gray-700">{{ __('konto.csv-no-transaction') }}</dt>
                        </div>
                    @endif
                </dl>
            @endif
        </div>
        <div wire:key="csv-upload-block">
            @if($account_id !== "")
                <div>
                    <x-intro level="2" :headline="__('konto.csv-upload-headline')" :sub-headline="__('konto.csv-upload-headline-sub')"/>
                    @if($data->isNotEmpty())
                        <flux:file-item
                            icon="document-check"
                            :heading="$csv->getClientOriginalName()"
                            :size="$csv->getSize()"
                        >
                            <x-slot:actions>
                                <flux:file-item.remove wire:click="clearCsv"/>
                            </x-slot:actions>
                        </flux:file-item>
                    @else
                        <flux:file-upload wire:model.live="csv">
                            <flux:file-upload.dropzone
                                :heading="__('konto.csv-draganddrop-fat-text')"
                                :text="__('konto.csv-draganddrop-sub-text')"
                                with-progress
                            />
                        </flux:file-upload>
                        <flux:error name="csv" />
                    @endif
                </div>
            @endif
        </div>

        @if($data->isNotEmpty())
            <div>
                <x-intro level="2" :headline="__('konto.csv.transaction.headline')" :sub-headline="__('konto.csv.transaction.headline-sub')"/>
                <div class="my-5">
                    <flux:switch
                        wire:click="reverseCsvOrder"
                        :checked="$csvOrderReversed"
                        :label="__('konto.manual-button-reverse-csv-order')"
                        :description="__('konto.manual-button-reverse-csv-order-sub')"
                        align="left"
                    />
                </div>
            </div>
        @endif
    </div>
    @if($data->isNotEmpty())
        <x-grid-list>
            @foreach($labels as $attr => $label)
                <x-grid-list.item-card wire:key="{{ $attr }}">
                    <flux:field>
                        <flux:label>{{ __($label) }}</flux:label>
                        <flux:description>{{ __("konto.hint.transaction.$attr") }}</flux:description>
                        <flux:select wire:model.live.change="mapping.{{ $attr }}" placeholder="">
                            <option value="">---Kein Import---</option>
                            @foreach($header as $csv_column_id => $value)
                                <option value="{{ $csv_column_id }}">
                                    {{ filled($value) ? $value : __('konto.csv-header-empty-column', ['n' => $csv_column_id + 1]) }}
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="mapping.{{ $attr }}"/>
                    </flux:field>
                    <x-slot:rows>
                        @isset($firstNewTransaction[$mapping[$attr]])
                            <x-grid-list.item-card.row label="konto.csv-preview-first">
                                {{ $this->formatDataView($firstNewTransaction[$mapping[$attr]], $attr) }}
                            </x-grid-list.item-card.row>
                        @endisset
                        @isset($lastNewTransaction[$mapping[$attr]])
                            <x-grid-list.item-card.row label="konto.csv-preview-last">
                                {{ $this->formatDataView($lastNewTransaction[$mapping[$attr]], $attr) }}
                            </x-grid-list.item-card.row>
                        @endisset
                    </x-slot:rows>
                </x-grid-list.item-card>
            @endforeach
        </x-grid-list>
        <div class="py-4 flex items-center">
            <flux:button variant="primary" icon="inbox-arrow-down" wire:click="save()">
                {{ __('konto.manual-button-assign') }}
            </flux:button>
        </div>
    @endif
</div>
