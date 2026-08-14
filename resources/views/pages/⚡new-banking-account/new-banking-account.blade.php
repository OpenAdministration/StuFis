<div>
    <div class="space-y-2">
        <flux:heading level="1" size="xl">{{ $this->label('headline') }}</flux:heading>
        <flux:text>{{ __('konto.new.headline-sub') }}</flux:text>
    </div>
    <flux:fieldset>
        <div class="space-y-6 mt-6">
            <flux:input wire:model.live.blur="short" class="max-w-16"
                        :label="__('konto.new.prefix-headline')"
                        placeholder="T"
                        :description="$this->label('prefix-headline-sub')"/>
            <flux:input wire:model.live.blur="name" class="max-w-sm"
                        :label="$this->label('name-headline')"
                        placeholder="Tagesgeld-Konto"
                        :description="__('konto.new.name-headline-sub')" />
            <div class="grid lg:grid-cols-2 gap-x-4 gap-y-6">
                <flux:input wire:model.live.blur="sync_from" type="date"
                            :label="__('konto.new.date-start-headline')"
                            :description="$this->label('date-start-headline-sub')"/>
                <flux:input wire:model.live.blur="sync_until" type="date"
                            :label="__('konto.new.date-end-headline')"
                            badge="optional"
                            :description="$this->label('date-end-headline-sub')"/>
            </div>

            {{-- Handed over by a bank access: the IBAN is the bank's own, and a synced account
                 must not be switched to manual entry. Both stay visible but locked. --}}
            <flux:input wire:model.live.blur="iban" :label="__('konto.new.iban')"
                        :badge="$bankSynced ? __('konto.new.from-bank-access') : 'optional'"
                        :readonly="$bankSynced"
                        :description="$bankSynced ? __('konto.new.iban-locked-sub') : __('konto.new.iban-sub')"
                        class="max-w-sm"/>

            <div class="my-6">
                <flux:switch wire:model.live.blur="manually_enterable"
                             :label="__('konto.new.manual-headline')"
                             :disabled="$bankSynced"
                             :description="$bankSynced ? __('konto.new.manual-locked-sub') : __('konto.new.manual-headline-sub')"
                             align="left"/>
            </div>

            <flux:button type="submit" variant="primary" wire:click="store">{{ $this->label('submit') }}</flux:button>
        </div>
    </flux:fieldset>
</div>
