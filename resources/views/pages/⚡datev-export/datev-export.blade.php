<div class="mt-8 sm:mx-8">
    <x-intro :headline="__('datev-export.headline')" :sub-headline="__('datev-export.sub-headline')" />

    <div class="max-w-(--breakpoint-lg) space-y-6 mt-6">

        {{-- Card 1: Budget Plan --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-5">
                <flux:select wire:model.live="hhpId" :label="__('datev-export.budget-plan.select-label')" variant="listbox">
                    @foreach($this->budgetPlans() as $plan)
                        <flux:select.option value="{{ $plan->id }}">{{ $plan->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- Card 2: Timespan + Date Field + PDF Toggle --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-8">
                <flux:field>
                    <flux:date-picker
                        mode="range"
                        wire:model.live="dateRange"
                        presets="thisMonth lastMonth thisQuarter lastQuarter custom"
                        :label="__('datev-export.timespan.label')"
                        selectable-header
                        :placeholder="__('datev-export.timespan.placeholder')"
                        :invalid="$errors->hasAny(['dateRange.start', 'dateRange.end'])"
                    />
                    <flux:error name="dateRange.start" />
                    <flux:error name="dateRange.end" />
                </flux:field>

                <flux:select
                    wire:model.live="dateField"
                    :label="__('datev-export.date-field.label')"
                    :description="__('datev-export.date-field.description')"
                    variant="listbox"
                >
                    @foreach($this->dateFields as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:switch
                    wire:model="exportPdfs"
                    :label="__('datev-export.pdf.label')"
                    :description="__('datev-export.pdf.description')"
                    align="left"
                />
            </div>
        </div>

        {{-- Card 3: Summary + detailed preview --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-4">
                @if($this->isExportable())
                    <div class="flex items-baseline gap-2">
                        <flux:heading size="lg">{{ $this->exportCount }}</flux:heading>
                        <flux:text>{{ __('datev-export.expenses-found') }}</flux:text>
                    </div>

                    @if($this->exportCount > 0)
                        <div class="mt-3" wire:init="loadPreview">
                            {{-- Loading placeholder (first load + each refresh) --}}
                            <div wire:loading.flex wire:target="loadPreview" class="items-center gap-2">
                                <flux:icon.loading variant="micro" />
                                <flux:text variant="subtle">{{ __('datev-export.preview.loading') }}</flux:text>
                            </div>

                            @if($previewLoaded)
                                <div wire:loading.remove wire:target="loadPreview" class="space-y-3">
                                    @if($previewStale)
                                        {{-- Filters changed: don't recompute the heavy list until asked --}}
                                        <div class="flex items-center justify-between gap-3">
                                            <flux:text variant="subtle">{{ __('datev-export.preview.stale') }}</flux:text>
                                            <flux:button size="sm" variant="primary" icon="arrow-path" wire:click="loadPreview">
                                                {{ __('datev-export.preview.refresh') }}
                                            </flux:button>
                                        </div>
                                    @else
                                        <div class="flex justify-end">
                                            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="loadPreview">
                                                {{ __('datev-export.preview.refresh') }}
                                            </flux:button>
                                        </div>

                                        <div class="max-h-96 overflow-y-auto">
                                            <flux:table>
                                                <flux:table.columns>
                                                    <flux:table.column>{{ __('datev-export.preview.columns.project') }}</flux:table.column>
                                                    <flux:table.column>{{ __('datev-export.preview.columns.invoice') }}</flux:table.column>
                                                    <flux:table.column>{{ __('datev-export.preview.columns.name') }}</flux:table.column>
                                                    <flux:table.column>{{ __('datev-export.preview.columns.beleg-date') }}</flux:table.column>
                                                    <flux:table.column>{{ __('datev-export.preview.columns.paid-at') }}</flux:table.column>
                                                    <flux:table.column align="center">{{ __('datev-export.preview.columns.bookings') }}</flux:table.column>
                                                </flux:table.columns>
                                                <flux:table.rows>
                                                    @foreach($this->previewRows as $row)
                                                        <flux:table.row :key="$row->expenseId">
                                                            <flux:table.cell>
                                                                <flux:link :href="route('project.show', $row->projectId)">P{{ $row->projectId }}</flux:link>
                                                            </flux:table.cell>
                                                            <flux:table.cell variant="strong">
                                                                <flux:link :href="route('legacy.expense', $row->expenseId)">{{ $row->invoiceId() }}</flux:link>
                                                            </flux:table.cell>
                                                            <flux:table.cell>{{ $row->name ?: '—' }}</flux:table.cell>
                                                            <flux:table.cell>{{ $row->belegDate?->format('d.m.Y') ?? '—' }}</flux:table.cell>
                                                            <flux:table.cell>{{ $row->paidAt?->format('d.m.Y') ?? '—' }}</flux:table.cell>
                                                            <flux:table.cell align="center">{{ $row->bookingCount }}</flux:table.cell>
                                                        </flux:table.row>
                                                    @endforeach
                                                </flux:table.rows>
                                            </flux:table>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                @else
                    <flux:text variant="subtle">{{ __('datev-export.summary.incomplete') }}</flux:text>
                @endif
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-4 py-4">
            <flux:button
                variant="primary"
                icon="arrow-down-tray"
                wire:click="export"
                wire:loading.attr="disabled"
                wire:target="export"
                :disabled="! $this->isExportable() || $this->exportCount === 0"
            >
                <span wire:loading.remove wire:target="export">{{ __('datev-export.export-button') }}</span>
                <span wire:loading wire:target="export">{{ __('datev-export.exporting') }}</span>
            </flux:button>
        </div>

    </div>
</div>
