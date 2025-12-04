<x-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header with Status and Actions -->
            <div class="mb-12">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="pl-4">
                        <h1 class="text-3xl font-bold text-gray-900">{{ __('project.view.header.title') }} {{ $project->id }}</h1>
                        <p class="text-sm text-gray-500 mt-1">{{ __('project.view.header.created_at') }} {{ $project->createdat->format('d.m.Y') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                            {{ $project->state->label() }}
                        </span>
                        <flux:modal.trigger name="status-modal">
                            <flux:button icon="arrow-path">{{ __('project.view.header.change_status') }}</flux:button>
                        </flux:modal.trigger>

                        <flux:button href="{{ route('legacy.projekt.edit', $project->id) }}" variant="primary" icon="pencil-square" color="indigo">
                            {{ __('project.view.header.edit') }}
                        </flux:button>

                        <flux:modal.trigger name="delete-modal">
                            <flux:button icon="trash" variant="danger">{{ __('project.view.header.delete') }}</flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>

                <!-- Budget Summary Cards -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.budget_summary.total') }}</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $project->posts()->sum('ausgaben') }}</p>
                            </div>
                            <div class="p-3 bg-indigo-100 rounded-lg">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.budget_summary.spent') }}</p>
                                <p class="text-2xl font-bold text-orange-600 mt-1">
                                    ???
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-lg">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.budget_summary.available') }}</p>
                                <p class="text-2xl font-bold mt-1"
                                   :class="totalRemaining > 0 ? 'text-green-600' : 'text-red-600'"
                                >
                                    ???
                                </p>
                            </div>
                            <div class="p-3 rounded-lg"
                                 :class="totalRemaining > 0 ? 'bg-green-100' : 'bg-red-100'">
                                <svg class="w-6 h-6"
                                     :class="totalRemaining > 0 ? 'text-green-600' : 'text-red-600'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.budget_summary.usage') }}</p>
                                <p class="text-2xl font-bold mt-1"
                                   :class="{
                                   'text-green-600': overallPercentUsed <= 50,
                                   'text-yellow-600': overallPercentUsed > 50 && overallPercentUsed <= 90,
                                   'text-red-600': overallPercentUsed > 90
                               }">
                                    drölf %
                                </p>
                            </div>
                            <div class="p-3 rounded-lg"
                                 :class="{
                                 'bg-green-100': overallPercentUsed <= 50,
                                 'bg-yellow-100': overallPercentUsed > 50 && overallPercentUsed <= 90,
                                 'bg-red-100': overallPercentUsed > 90
                             }">
                                <svg class="w-6 h-6"
                                     :class="{
                                     'text-green-600': overallPercentUsed <= 50,
                                     'text-yellow-600': overallPercentUsed > 50 && overallPercentUsed <= 90,
                                     'text-red-600': overallPercentUsed > 90
                                 }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approval Section -->
            <div class="bg-white rounded-2xl shadow-accent border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('project.view.approval.heading') }}</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('project.view.approval.legal_basis') }}</label>
                        @isset($project->recht)
                            <p class="text-gray-900">{{ $project->getLegal()['label'] }}</p>
                        @else
                            <p class="text-gray-500 italic">{{ __('project.view.approval.none') }}</p>
                        @endisset
                    </div>
                    <div>
                        @if($project->getLegal())
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $project->getLegal()['label-additional'] }}</label>
                            @if($project->recht_additional)
                                <p class="text-gray-900">{{ $project->recht_additional }}</p>
                            @else
                                <p class="text-gray-500 italic">{{ __('project.view.approval.none') }}</p>
                            @endif
                        @endif
                    </div>
                    <div class="lg:col-span-2">
                        <p class="text-sm text-gray-500 mt-1">{{ $project->getLegal()['hint-text'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Project Details -->
            <div class="bg-white rounded-2xl shadow-accent border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('project.view.details.heading') }}</h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('project.view.details.name') }}</label>
                        <p class="text-gray-900 font-medium">{{ $project->name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('project.view.details.responsible') }}</label>
                        <a href="mailto:{{ $project->responsible }}"
                           class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $project->responsible }}
                        </a>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('project.view.details.org') }}</label>
                        <p class="text-gray-900">{{ $project->org }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('project.view.details.period') }}</label>
                        <p class="text-gray-900">
                            <span class="font-medium">{{ __('project.view.details.from') }}</span> {{ $project->date_start->format('d.m.Y') }}
                            <span class="font-medium mx-2">{{ __('project.view.details.to') }}</span> {{ $project->date_end->format('d.m.Y') }}
                        </p>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('project.view.details.link') }}</label>
                        <p class="text-gray-500 italic">{{ __('project.view.details.none') }}</p>
                    </div>

                </div>
            </div>

            <!-- Budget Table -->
            <div class="bg-white rounded-2xl shadow-accent border border-gray-200 overflow-hidden mb-6" x-data="budgetTable()">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">{{ __('project.view.budget_table.heading') }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ __('project.view.budget_table.subheading') }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('project.view.budget_table.nr') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('project.view.budget_table.group') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('project.view.budget_table.remark') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('project.view.budget_table.title') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('project.view.budget_table.income') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('project.view.budget_table.expenses') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('project.view.budget_table.claimed') }}
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('project.view.budget_table.status') }}
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($project->posts as $post)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $loop->iteration }}.</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $post->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 italic">{{ $post->bemerkung }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 italic">{{ $post->titel_id }}</td>
                                <td @class(["px-6 py-4 whitespace-nowrap text-sm text-right font-medium",
                                        "text-green-600" => $post->einnahmen > 0,
                                        "text-gray-400" => $post->einnahmen === "0.00",
                                ])>{{ $post->einnahmen }}</td>
                                <td  @class(["px-6 py-4 whitespace-nowrap text-sm text-right font-medium",
                                        "text-gray-900" => $post->ausgaben > 0,
                                        "text-gray-400" => $post->ausgaben === "0.00",
                                ])>
                                    {{ $post->ausgaben }}
                                </td>
                                <td @class(["px-6 py-4 whitespace-nowrap text-sm text-right font-medium", "text-gray-400"])>
                                    {{ $post->expensePosts()->get() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($post->expensePosts()->exists())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            N/A
                                        </span>
                                    @else
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-full bg-gray-200 rounded-full h-2 max-w-[100px]">
                                                <div class="h-2 rounded-full transition-all duration-300"
                                                     :class="{
                                                         'bg-green-500': row.percentUsed <= 50,
                                                         'bg-yellow-500': row.percentUsed > 50 && row.percentUsed <= 90,
                                                         'bg-red-500': row.percentUsed > 90
                                                     }"
                                                     :style="`width: ${Math.min(row.percentUsed, 100)}%`"></div>
                                            </div>
                                            <span class="text-xs font-medium"
                                                  :class="{
                                                      'text-green-600': row.percentUsed <= 50,
                                                      'text-yellow-600': row.percentUsed > 50 && row.percentUsed <= 90,
                                                      'text-red-600': row.percentUsed > 90
                                                  }"
                                                  x-text="Math.round(row.percentUsed) + '%'"></span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right text-sm font-bold text-gray-900">Summe</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-green-600">
                                {{ $project->posts()->sum('einnahmen') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                {{ $project->posts()->sum('ausgaben') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-orange-600">
                                ???
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="w-full bg-gray-200 rounded-full h-2 max-w-[100px]">
                                        <div class="h-2 rounded-full transition-all duration-300"
                                             :class="{
                                                 'bg-green-500': overallPercentUsed <= 50,
                                                 'bg-yellow-500': overallPercentUsed > 50 && overallPercentUsed <= 90,
                                                 'bg-red-500': overallPercentUsed > 90
                                             }"
                                             :style="`width: ${Math.min(overallPercentUsed, 100)}%`"></div>
                                    </div>
                                    <span class="text-xs font-bold"
                                          :class="{
                                              'text-green-600': overallPercentUsed <= 50,
                                              'text-yellow-600': overallPercentUsed > 50 && overallPercentUsed <= 90,
                                              'text-red-600': overallPercentUsed > 90
                                          }"
                                          x-text="Math.round(overallPercentUsed) + '%'"></span>
                                </div>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Project Description -->
            <div class="bg-white rounded-2xl shadow-accent border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('project.view.description.heading') }}</h2>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $project->beschreibung }}</p>
            </div>

            <!-- Expenses Section -->
            <div class="bg-white rounded-2xl shadow-accent border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('project.view.expenses.heading') }}</h2>
                @if($project->expenses_count > 0)
                @else
                    <div class="bg-gray-50 rounded-lg p-4 text-center text-gray-500">
                        {{ __('project.view.expenses.none') }}
                    </div>
                @endif
            </div>

        </div>

        <div class="mt-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:chat-panel target-type="projekt" :target-id="$project->id"/>
        </div>

        <!-- Status Change Modal -->
        <flux:modal name="status-modal" class="min-w-96">
            <div>
                <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">{{ __('project.view.status_modal.heading') }}</h3>
                <flux:select variant="listbox" placeholder="{{ __('project.view.status_modal.placeholder') }}">
                    @foreach($project->state->transitionableStateInstances() as $state)
                        <flux:select.option>{{ $state->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button @click="showStatusModal = false"
                        type="button"
                        class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-accent px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-1 sm:text-sm">
                    {{ __('project.view.status_modal.cancel') }}
                </button>
                <button type="button"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-accent px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                    {{ __('project.view.status_modal.save') }}
                </button>
            </div>
        </flux:modal>

        <!-- Delete Modal -->
        <flux:modal name="delete-modal">
            <div class="flex items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div>
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-5">
                        <h3 class="text-lg leading-6 font-bold text-gray-900">{{ __('project.view.delete_modal.heading') }}</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">{{ __('project.view.delete_modal.intro') }}</p>
                            <ul class="mt-2 text-sm text-gray-500 text-left list-disc list-inside">
                                <li>{{ __('project.view.delete_modal.conditions.owner') }}</li>
                                <li>{{ __('project.view.delete_modal.conditions.no_expenses') }}</li>
                            </ul>
                            <p class="mt-2 text-sm text-gray-500">{{ __('project.view.delete_modal.warning') }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button @click="showDeleteModal = false"
                            type="button"
                            class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-accent px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-1 sm:text-sm">
                        {{ __('project.view.delete_modal.cancel') }}
                    </button>
                    <button type="button"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-accent px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:col-start-2 sm:text-sm">
                        {{ __('project.view.delete_modal.confirm') }}
                    </button>
                </div>
            </div>
        </flux:modal>
    </div>
</x-layout>
