<div>
    <x-intro :headline="__('settings.headline')" :sub-headline="__('settings.sub-headline')" />

    <form wire:submit="save" class="space-y-6 mt-6">

        {{-- General --}}
        <flux:card class="space-y-6">
            <flux:heading size="lg">{{ __('settings.groups.general') }}</flux:heading>

            <flux:input
                type="email"
                wire:model="financeMail"
                :label="__('settings.finance-mail.label')"
                :description="__('settings.finance-mail.description')"
            />
            <flux:input
                wire:model="mailDomain"
                :label="__('settings.mail-domain.label')"
                :description="__('settings.mail-domain.description')"
            />
        </flux:card>

        {{-- Project --}}
        <flux:card class="space-y-6">
            <flux:heading size="lg">{{ __('settings.groups.project') }}</flux:heading>

            <div class="grid sm:grid-cols-2 gap-6">
                <flux:input
                    type="number"
                    min="0"
                    wire:model="descMin"
                    :label="__('settings.description-min.label')"
                    :description="__('settings.description-min.description')"
                />
                <flux:input
                    type="number"
                    min="-1"
                    wire:model="descMax"
                    :label="__('settings.description-max.label')"
                    :description="__('settings.description-max.description')"
                />
            </div>

            <flux:switch
                wire:model="protocolActive"
                :label="__('settings.protocol-active.label')"
                :description="__('settings.protocol-active.description')"
                align="left"
            />
            <flux:input
                wire:model="protocolLabel"
                :label="__('settings.protocol-label.label')"
            />
        </flux:card>

        {{-- Committees --}}
        <flux:card class="space-y-6">
            <flux:heading size="lg">{{ __('settings.groups.committees') }}</flux:heading>

            <flux:select
                wire:model="committeeMode"
                variant="listbox"
                :label="__('settings.committee-mode.label')"
                :description="__('settings.committee-mode.description')"
            >
                @foreach(['filter', 'all', 'raw'] as $mode)
                    <flux:select.option value="{{ $mode }}">{{ __('settings.committee-mode.options.' . $mode) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea
                wire:model="committeesData"
                rows="6"
                :label="__('settings.committee-data.label')"
                :description="__('settings.committee-data.description')"
            />
        </flux:card>

        {{-- Features --}}
        <flux:card class="space-y-6">
            <flux:heading size="lg">{{ __('settings.groups.features') }}</flux:heading>

            <flux:switch
                wire:model="taxActive"
                :label="__('settings.tax-active.label')"
                :description="__('settings.tax-active.description')"
                align="left"
            />
            <flux:switch
                wire:model="datev"
                :label="__('settings.datev.label')"
                :description="__('settings.datev.description')"
                align="left"
            />
        </flux:card>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary" icon="check">
                {{ __('settings.save-button') }}
            </flux:button>
        </div>

    </form>
</div>
