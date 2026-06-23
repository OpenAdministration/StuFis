<div>
    <x-intro :headline="__('settings.headline')" :sub-headline="__('settings.sub-headline')" />

    <form wire:submit="save" class="space-y-6 mt-6">

        {{-- General --}}
        <flux:card class="space-y-6">
            <flux:heading size="lg">{{ __('settings.groups.general') }}</flux:heading>

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

        {{-- Legal bases (Rechtsgrundlagen) --}}
        <flux:card class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ __('settings.legal-bases.heading') }}</flux:heading>
                <flux:button size="sm" icon="plus" wire:click="addLegalBasis">
                    {{ __('settings.legal-bases.add') }}
                </flux:button>
            </div>
            <flux:text>{{ __('settings.legal-bases.description') }}</flux:text>

            <div wire:sort="sortLegalBases" class="space-y-4">
                @forelse ($legalBases as $index => $basis)
                    <div
                        wire:key="legal-basis-{{ $basis['_key'] }}"
                        wire:sort:item="{{ $basis['_key'] }}"
                        class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-600 dark:bg-zinc-700/50"
                    >
                        <button
                            type="button"
                            wire:sort:handle
                            class="mt-1 cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                            :aria-label="__('settings.legal-bases.reorder')"
                        >
                            <flux:icon.bars-3 variant="micro" />
                        </button>

                        <div class="flex-1 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="grid sm:grid-cols-2 gap-4 flex-1">
                                    <flux:input
                                        wire:model="legalBases.{{ $index }}.slug"
                                        :label="__('settings.legal-bases.slug')"
                                        :readonly="$basis['id'] !== null"
                                    />
                                    <flux:input
                                        wire:model="legalBases.{{ $index }}.label"
                                        :label="__('settings.legal-bases.label')"
                                    />
                                    <flux:input
                                        wire:model="legalBases.{{ $index }}.label_additional"
                                        :label="__('settings.legal-bases.label-additional')"
                                    />
                                    <flux:input
                                        wire:model="legalBases.{{ $index }}.placeholder"
                                        :label="__('settings.legal-bases.placeholder')"
                                    />
                                </div>
                                <flux:button
                                    size="sm"
                                    variant="subtle"
                                    icon="trash"
                                    wire:click="removeLegalBasis({{ $index }})"
                                    :tooltip="__('settings.legal-bases.remove')"
                                />
                            </div>

                            <flux:textarea
                                wire:model="legalBases.{{ $index }}.hint_text"
                                rows="2"
                                :label="__('settings.legal-bases.hint-text')"
                            />

                            <flux:switch
                                wire:model="legalBases.{{ $index }}.is_active"
                                :label="__('settings.legal-bases.active')"
                                align="left"
                            />
                        </div>
                    </div>
                @empty
                    <flux:text variant="subtle">{{ __('settings.legal-bases.empty') }}</flux:text>
                @endforelse
            </div>
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
