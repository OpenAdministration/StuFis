<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                {{ $isNew ? __('project.view.edit.title_new') : __('project.view.edit.title_existing') }}
            </h1>
            @if (!$isNew)
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('project.view.edit.subtitle_existing', ['id' => $project_id, 'state' => $state->label()]) }}
                </p>
            @else
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('project.view.edit.subtitle_new') }}
                </p>
            @endif
        </div>

        {{-- Form Actions --}}
        <div class="flex flex-col space-y-4 mt-6">
            <div class="flex flex-col items-end space-y-4">
                <div class="flex items-center space-x-4">
                    <flux:button :href="$isNew ? url()->previous() : route('project.show', $project_id)" variant="outline" icon="arrow-left">{{ __('project.view.edit.back') }}</flux:button>
                    <flux:button wire:click="saveAs('{{ $state_name }}')" variant="primary">
                        {{ __('project.view.edit.save_as', ['state' => $this->getState()->label()]) }}
                    </flux:button>
                </div>
                @error('save')
                <p class="text-red-600 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Approval Section (only visible for non-draft projects) --}}
    @if($state_name !== 'draft')
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="sm:col-span-2 flex justify-between items-start">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('project.view.approval.heading') }}</h3>
                    <flux:tooltip toggleable class="-mt-0 -mr-0">
                        <flux:button size="sm" variant="ghost" square>
                            <x-fas-info-circle class="text-gray-500 size-4"/>
                        </flux:button>
                        <flux:tooltip.content class="max-w-[20rem] space-y-2">
                            {{ __('project.view.approval.info_toggle') }}
                        </flux:tooltip.content>
                    </flux:tooltip>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Rechtsgrundlage Dropdown --}}
                    <flux:select wire:model.live="recht" :label="__('project.view.approval.legal_basis')" variant="listbox">
                        @foreach ($rechtsgrundlagen as $rg)
                            <flux:select.option value="{{ $rg['key'] }}">{{ $rg['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Dynamic Additional Fields per Rechtsgrundlage --}}
                    <div>
                        @isset ($rechtsgrundlagen[$recht]['has_additional'])
                            <flux:input wire:model="recht_additional"
                                        :label="$rechtsgrundlagen[$recht]['label_additional']"
                                        placeholder="{{ $rechtsgrundlagen[$recht]['placeholder'] ?? '' }}"/>
                        @endisset
                    </div>
                    <div class="sm:col-span-2">
                        @if (isset($rechtsgrundlagen[$recht]['hint']))
                            <p class="mt-2 text-sm text-gray-500">{{ $rechtsgrundlagen[$recht]['hint'] }}</p>
                        @endisset
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Project Information --}}
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="sm:col-span-2 flex justify-between items-start">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('project.view.details.heading_edit') }}</h3>
                <flux:tooltip toggleable class="-mt-0 -mr-0">
                    <flux:button size="sm" variant="ghost" square>
                        <x-fas-info-circle class="text-gray-500 size-4"/>
                    </flux:button>
                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                        {{ __('project.view.details.info_toggle') }}
                    </flux:tooltip.content>
                </flux:tooltip>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- Project Name --}}
                <div class="">
                    <flux:input type="text" :label="__('project.view.details.name')" wire:model="name" />
                </div>

                {{-- Responsible Person --}}
                <div class="">
                    <flux:field>
                        <flux:label>{{ __('project.view.details.responsible_email') }}</flux:label>
                        <flux:input.group>
                            <flux:input type="email" wire:model="responsible" />
                            {{-- <flux:input.group.suffix>@domain.com</flux:input.group.suffix> --}}
                        </flux:input.group>
                    </flux:field>
                </div>

                {{-- Organization --}}
                <div>
                    <flux:select wire:model="org" :label="__('project.view.details.org')" variant="listbox" searchable>
                        @foreach ($gremien as $label)
                            <flux:select.option>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Organization Mail --}}
                @if (false)
                    <div>
                        <flux:select type="email" :label="__('project.view.details.org_email')" wire:model="org_mail">
                            @foreach($mailingLists as $mailingLists)
                                <flux:select.option value="{{ $mailingLists }}">{{ $mailingLists }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif

                {{-- Project Duration --}}
                <div>
                    <flux:field>
                        <flux:date-picker mode="range" wire:model="dateRange"
                                          :label="__('project.view.details.period')" selectable-header
                                          :placeholder="__('project.view.details.period_placeholder')"
                                          :invalid="$this->getErrorBag()->hasAny(['date_end'])"
                        />
                        <flux:error name="dateRange" />
                        <flux:error name="date_end" />
                    </flux:field>
                </div>

                {{-- Protocol Link (optional based on config) --}}
                @if ($protocolLinkSetting->active)
                    <div class="sm:col-span-2">
                        <flux:input type="text" :label="config('stufis.project.link-label', __('project.view.details.fallback_link'))" wire:model="protokoll" />
                    </div>
                @endif

                {{-- Creation Date --}}
                <div class="">
                    @if($canUpdateBudgetPlan)
                        <flux:select variant="listbox" :label="__('project.view.edit.project_belongs_to_budget_plan')" wire:model="hhp_id">
                            @foreach ($budgetPlans as $plan)
                                <flux:select.option value="{{ $plan->id }}">{{ $plan->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:label>{{ __('project.view.edit.project_belongs_to_budget_plan') }}</flux:label>
                        <span class="text-gray-500">{{ $budgetPlans->find($hhp_id)->label() }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Project Posts (Budget Items) Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <div>
            <div class="flex items-center justify-between mb-4 px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('project.view.budget_table.heading') }}</h3>
                <flux:tooltip toggleable class="-mt-0 -mr-0">
                    <flux:button size="sm" variant="ghost" square>
                        <x-fas-info-circle class="text-gray-500 size-4"/>
                    </flux:button>
                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                        {{ __('project.view.budget_table.info_toggle') }}
                    </flux:tooltip.content>
                </flux:tooltip>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                            {{ __('project.view.budget_table.nr') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ __('project.view.budget_table.group') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ __('project.view.budget_table.remark') }}
                        </th>
                        @if($canUpdateBudget)
                            <th scope="col" class="min-w-48 px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                {{ __('project.view.budget_table.title') }}
                            </th>
                        @endif
                        <th scope="col" class="w-36 px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ __('project.view.budget_table.income_edit') }}
                        </th>
                        <th scope="col" class="w-36 px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ __('project.view.budget_table.expenses_edit') }}
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ __('project.view.edit.actions') }}</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach ($posts as $index => $post)
                        <tr class="hover:bg-gray-50" wire:key="post-{{ $index }}">
                            {{-- Row Number --}}
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                {{ $loop->iteration }}.
                            </td>

                            {{-- Post Name --}}
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <flux:input wire:model="posts.{{ $index }}.name"
                                            wire:key="post-{{ $index }}-name"
                                            value="{{ $post['name'] }}"
                                />
                                <flux:error name="posts.{{ $index }}.name" />
                            </td>

                            {{-- Remarks --}}
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <flux:input wire:model="posts.{{ $index }}.bemerkung"
                                            :placeholder="__('project.view.budget_table.remark_placeholder')"/>
                                <flux:error name="posts.{{ $index }}.bemerkung" />
                            </td>

                            {{-- Budget Title --}}
                            @if($canUpdateBudget)
                                <td class="px-3 py-4 text-sm text-gray-900">
                                    @if ($post["readonly"] === true)
                                        @php $title = $budgetTitles->find($post['titel_id']) @endphp
                                        <span class="text-gray-500">{{ $title->titel_name }} ({{ $title->titel_nr }})</span>
                                    @else
                                        <flux:select variant="listbox" wire:model="posts.{{ $index }}.titel_id" searchable>
                                        @foreach ($budgetTitles as $title)
                                            <flux:select.option value="{{ $title->id }}">
                                                {{ $title->titel_name }}
                                                <span class="text-gray-500 ml-2">{{ $title->titel_nr }}</span>
                                            </flux:select.option>
                                        @endforeach
                                        </flux:select>
                                        <flux:error name="posts.{{ $index }}.titel_id" />
                                    @endif
                                </td>
                            @endif

                            {{-- Income --}}
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <x-money-input wire:model.blur="posts.{{ $index }}.einnahmen" :disabled="!$posts[$index]['ausgaben']->isZero()"/>
                            </td>

                            {{-- Expenses --}}
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <x-money-input wire:model.blur="posts.{{ $index }}.ausgaben" :disabled="!$posts[$index]['einnahmen']->isZero()"/>
                            </td>

                            {{-- Actions --}}
                            <td class="relative whitespace-nowrap py-4 text-right text-sm font-medium sm:pr-6">
                                @if($this->isPostDeletable($index))
                                    <flux:button wire:click="removePost({{ $index }})" variant="ghost" square>
                                        <x-fas-trash class="text-red-500 size-4"/>
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                    <tr>
                        <td></td>
                        <td colspan="{{ $canUpdateBudget ? '2' : '1' }}" class="px-3 py-3.5">
                            <flux:button wire:click="addEmptyPost" icon="plus" variant="primary">{{ __('project.view.edit.add_post') }}</flux:button>
                        </td>
                        <td class="py-3.5 pl-4 pr-3 text-right text-sm font-semibold text-gray-900 sm:pl-6">
                            {{ __('project.view.budget_table.sums') }}
                        </td>
                        <td class="px-3 py-3.5 text-sm font-semibold text-gray-900">
                            <div class="flex items-center justify-between">
                                <span>{{ $this->getTotalIncome() }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-3.5 text-sm font-semibold text-gray-900">
                            <div class="flex items-center justify-between">
                                <span>{{ $this->getTotalExpenses() }}</span>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Project Description --}}
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6 ">
            <div class="flex justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('project.view.description.heading') }}</h3>
                <flux:tooltip toggleable class="-mt-0 -mr-0">
                    <flux:button size="sm" variant="ghost" square>
                        <x-fas-info-circle class="text-gray-500 size-4"/>
                    </flux:button>
                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                        {{ __('project.view.description.info_toggle') }}
                    </flux:tooltip.content>
                </flux:tooltip>
            </div>
            <div>
                <flux:editor
                    wire:model="beschreibung"
                    :placeholder="__('project.view.description.placeholder')"
                />
            </div>
        </div>
    </div>

    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">


            <flux:file-upload wire:model="newAttachments" multiple :label="__('project.view.attachments.upload_label')">
                <flux:file-upload.dropzone
                    :heading="__('project.view.attachments.dropzone_heading')"
                    :text="__('project.view.attachments.dropzone_text')"
                    with-progress
                />
            </flux:file-upload>
            <div class="mt-4 flex flex-col gap-2">
                <div class="mt-2 flex flex-col gap-2">
                    @foreach($newAttachments as $attachment)
                        <x-file-card
                            :heading="$attachment->getClientOriginalName()"
                            :size="$attachment->getSize()"
                            icon="arrow-up-tray"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeNewAttachment({{ $loop->index }})"/>
                            </x-slot>
                        </x-file-card>
                    @endforeach
                    @foreach($existingAttachments as $attachment)
                        <x-file-card
                            :heading="$attachment['name']"
                            :size="$attachment['size']"
                            :icon="$attachment['mime_type']"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeExistingAttachment({{ $attachment['id'] }})"/>
                            </x-slot>
                        </x-file-card>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="flex flex-col space-y-4 mt-6">
        <div class="flex flex-col items-end space-y-4">
            <div class="flex items-center space-x-4">
                <flux:button :href="$isNew ? url()->previous() : route('project.show', $project_id)" variant="outline" icon="arrow-left">{{ __('project.view.edit.back') }}</flux:button>
                <flux:button wire:click="saveAs('{{ $state_name }}')" variant="primary">
                    {{ __('project.view.edit.save_as', ['state' => $this->getState()->label()]) }}
                </flux:button>
                @if($this->getState()->equals(\App\States\Project\Draft::class))
                    <flux:button wire:click="saveAs('wip')" variant="primary">
                        {{ __('project.view.edit.save_as_requested') }}
                    </flux:button>
                @endif
            </div>
            @error('save')
            <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
