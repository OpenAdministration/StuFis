<flux:main class="max-w-4xl">
    <div class="space-y-2">
        <flux:heading level="1" size="xl">{{ __('konto.new.headline') }}</flux:heading>
        <flux:text>{{ __('konto.new.headline-sub') }}</flux:text>
    </div>
    <flux:fieldset>
        <div class="space-y-6 mt-6">
            <flux:input wire:model.blur="short" class="max-w-16"
                        :label="__('konto.new.prefix-headline')"
                        placeholder="T"
                        :description="__('konto.new.prefix-headline-sub')"/>
            <flux:input wire:model.blur="name" class="max-w-sm"
                        :label="__('konto.new.name-headline')"
                        placeholder="Tagesgeld-Konto"
                        :description="__('konto.new.name-headline-sub')" />
            <div class="grid lg:grid-cols-2 gap-x-4 gap-y-6">
                <flux:input wire:model.blur="sync_from" type="date"
                            :label="__('konto.new.date-start-headline')"
                            :description="__('konto.new.date-start-headline-sub')"/>
                <flux:input wire:model.blur="sync_until" type="date"
                            :label="__('konto.new.date-end-headline')"
                            badge="optional"
                            :description="__('konto.new.date-end-headline-sub')"/>
            </div>

            <flux:input wire:model.blur="iban" :label="__('konto.new.iban')" badge="optional" class="max-w-sm"/>

            <div class="my-6">
                <flux:switch wire:model.blur="manually_enterable" :label="__('konto.new.manual-headline')" :description="__('konto.new.manual-headline-sub')" align="left"/>
            </div>

            <flux:button type="submit" variant="primary" wire:click="store">Speichern</flux:button>
        </div>
    </flux:fieldset>
</flux:main>
