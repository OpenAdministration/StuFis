@php use Cknow\Money\Money; @endphp

@php $totalAusgaben = $project->totalAusgaben() @endphp
@php $totalRemainingAusgaben = $project->totalRemainingAusgaben() @endphp
@php $totalRatioAusgaben = $project->totalRatioAusgaben(); @endphp

@php $totalEinnahmen = $project->totalEinnahmen() @endphp
@php $totalRemainingEinnahmen = $project->totalRemainingEinnahmen() @endphp
@php $totalRatioEinnahmen = $project->totalRatioEinnahmen(); @endphp

<div>
    <div class="space-y-4">

        <!-- Header with Status and Actions -->
        <div>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="pl-4">
                    <h1 class="text-3xl font-bold text-gray-900">{{ __('project.view.header.title') }} {{ $project->id }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ __('project.view.header.created_at') }} {{ $project->createdat?->format('d.m.Y') }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- Button Row --}}
                    <flux:modal.trigger name="state-modal">
                        <flux:button icon="arrow-path">
                            {{ __('project.view.header.change_status') }}
                        </flux:button>
                    </flux:modal.trigger>
                    @can('update', $project)
                        <flux:button href="{{ route('project.edit', $project->id) }}" variant="primary"
                                     icon="pencil-square" color="indigo"
                        >
                            {{ __('project.view.header.edit') }}
                        </flux:button>
                    @else
                        <flux:tooltip content="{{ __('project.view.header.edit-not-possible_tooltip') }}">
                            <div><flux:button variant="outline" icon="pencil-square" disabled variant="primary">
                                    {{ __('project.view.header.edit') }}
                                </flux:button></div>
                        </flux:tooltip>
                    @endcan
                    @can('create-expense', $project)
                        <flux:button href="{{ route('legacy.expense.create', $project->id) }}" variant="primary"
                                     icon="plus" color="green"
                        >
                            {{ __('project.view.header.new-expense') }}
                        </flux:button>
                    @else
                        <flux:tooltip content="{{ __('project.view.header.new-expense-not-possible_tooltip') }}">
                            <div><flux:button variant="primary" icon="plus" color="green" disabled>
                                    {{ __('project.view.header.new-expense') }}
                                </flux:button></div>
                        </flux:tooltip>
                    @endcan

                    <flux:dropdown position="bottom" align="end">
                        <flux:button icon="ellipsis-vertical"/>

                        <flux:menu>
                            <flux:menu.item icon="trash" variant="danger" x-on:click="$flux.modal('delete-modal').show()">
                                {{ __('project.view.header.delete') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 my-6">
            <!-- State Card -->
            <flux:card size="sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.summary_cards.state') }}</p>
                        <p @class([
                            "font-bold mt-1",
                            "text-zinc-600" => $project->state->color() === "zinc",
                            "text-sky-600" => $project->state->color() === "sky",
                            "text-yellow-600" => $project->state->color() === "yellow",
                            "text-green-600" => $project->state->color() === "green",
                            "text-rose-600" => $project->state->color() === "rose",
                        ])>
                            {{ $project->state->label() }}
                        </p>
                    </div>
                    <div @class(["p-3 rounded-lg",
                                "bg-zinc-200" => $project->state->color() === "zinc",
                                "bg-sky-200" => $project->state->color() === "sky",
                                "bg-yellow-200" => $project->state->color() === "yellow",
                                "bg-green-200" => $project->state->color() === "green",
                                "bg-rose-200" => $project->state->color() === "rose",
                            ])>
                        <x-dynamic-component :component="$project->state->iconName()" @class(["w-6 h-6",
                                    "text-zinc-600" => $project->state->color() === "zinc",
                                    "text-sky-600" => $project->state->color() === "sky",
                                    "text-yellow-600" => $project->state->color() === "yellow",
                                    "text-green-600" => $project->state->color() === "green",
                                    "text-rose-600" => $project->state->color() === "rose",
                                ])/>
                    </div>
                </div>
            </flux:card>
            <!-- Total out Card -->
            <flux:card size="sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.summary_cards.out_total') }}</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">
                            {{ $project->posts()->sumMoney('ausgaben') }}
                        </p>
                    </div>
                    <div class="p-3 bg-teal-100 rounded-lg">
                        <x-far-check-circle class="w-6 h-6 text-teal-600" />
                    </div>
                </div>
            </flux:card>
            <!-- Remaining Expense Card -->
            <flux:card size="sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.summary_cards.out_available') }}</p>
                        <p @class([ "text-2xl font-bold mt-1",
                                    'text-green-600' => $totalRatioAusgaben <  75,
                                    'text-yellow-600' => 75 <= $totalRatioAusgaben && $totalRatioAusgaben <=100,
                                    'text-red-600' => $totalRatioAusgaben >  100,
                        ])>
                            {{ $totalRemainingAusgaben }}
                        </p>
                    </div>
                    <div @class([
                                "p-3 rounded-lg",
                                'bg-green-100' => $totalRatioAusgaben <  75,
                                'bg-yellow-100' => 75 <= $totalRatioAusgaben && $totalRatioAusgaben <=100,
                                'bg-red-100' => $totalRatioAusgaben >  100,
                            ])>
                        <x-fas-euro-sign @class(['size-6',
                            'text-green-600' => $totalRatioAusgaben <  75,
                            'text-yellow-600' => 75 <= $totalRatioAusgaben && $totalRatioAusgaben <=100,
                            'text-red-600' => $totalRatioAusgaben >  100,
                        ])/>
                    </div>
                </div>
            </flux:card>
            <!-- Ratio Out Card -->
            <flux:card size="sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.summary_cards.out_ratio') }}</p>
                        <p @class([
                                "text-2xl font-bold mt-1",
                                'text-yellow-600' => $totalRatioAusgaben <  75,
                                'text-green-600' => 75 <= $totalRatioAusgaben && $totalRatioAusgaben <=100,
                                'text-red-600' => $totalRatioAusgaben >  100,
                            ])>
                            {{ $totalRatioAusgaben }} %
                        </p>
                    </div>
                    <div @class([
                            "p-3 rounded-lg",
                            'bg-yellow-100' => $totalRatioAusgaben <  75,
                            'bg-green-100' => 75 <= $totalRatioAusgaben && $totalRatioAusgaben <=100,
                            'bg-red-100' => $totalRatioAusgaben >  100,
                        ])>
                        <x-fas-chart-simple @class(['size-6',
                                'text-yellow-600' => $totalRatioAusgaben <  75,
                                'text-green-600' => 75 <= $totalRatioAusgaben && $totalRatioAusgaben <=100,
                                'text-red-600' => $totalRatioAusgaben >  100,
                        ])/>
                    </div>
                </div>
            </flux:card>

            <!-- BudgetPlan Card -->
            <flux:card size="sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.summary_cards.budgetplan') }}</p>
                        <p class="font-bold text-gray-900 mt-1">
                            {{ $project->relatedBudgetPlan()->label() }}
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 rounded-lg">
                        <x-fas-bars-staggered class="w-6 h-6 text-indigo-600" />
                    </div>
                </div>
            </flux:card>
            <!-- Total in Card -->
            <flux:card size="sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.summary_cards.in_total') }}</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">
                            {{ $totalEinnahmen }}
                        </p>
                    </div>
                    <div class="p-3 bg-teal-100 rounded-lg">
                        <x-far-check-circle class="w-6 h-6 text-teal-600" />
                    </div>
                </div>
            </flux:card>
            <!-- Used In Card -->
            <flux:card size="sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.summary_cards.in_available') }}</p>
                        <p @class([ "text-2xl font-bold mt-1",
                                    'text-green-600' => $totalRatioEinnahmen >= 100,
                                    'text-yellow-600' => $totalRatioEinnahmen < 100,
                                ])>
                            {{ $totalRemainingEinnahmen }}
                        </p>
                    </div>
                    <div @class([
                                "p-3 rounded-lg",
                                'bg-green-100' => $totalRatioEinnahmen >= 100,
                                'bg-yellow-100' => $totalRatioEinnahmen < 100,
                            ])>
                        <x-fas-euro-sign @class(['size-6',
                            'text-green-600' => $totalRatioEinnahmen >= 100,
                            'text-yellow-600' => $totalRatioEinnahmen < 100,
                        ])/>
                    </div>
                </div>
            </flux:card>
            <!-- Ratio In Card -->
            <flux:card size="sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ __('project.view.summary_cards.in_ratio') }}</p>
                        <p @class([
                                "text-2xl font-bold mt-1",
                                'text-yellow-600' => $totalRatioEinnahmen < 100,
                                'text-green-600' => $totalRatioEinnahmen >= 100,
                            ])>
                            {{ $totalRatioEinnahmen }} %
                        </p>
                    </div>
                    <div @class([
                            "p-3 rounded-lg",
                            'bg-yellow-100' => $totalRatioEinnahmen < 100,
                            'bg-green-100' => $totalRatioEinnahmen >= 100,
                        ])>
                        <x-fas-chart-simple @class(['size-6',
                            'text-yellow-600' => $totalRatioEinnahmen < 100,
                            'text-green-600' => $totalRatioEinnahmen >= 100,
                        ])/>
                    </div>
                </div>
            </flux:card>

        </div>

        @if($showApproval)
            <!-- Approval Section -->
            <flux:card>
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('project.view.approval.heading') }}</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('project.view.approval.legal_basis') }}
                        </label>
                        @empty($project->recht)
                            <p class="text-gray-500 italic">{{ __('project.view.approval.none') }}</p>
                        @else
                            <p class="text-gray-900">{{ $project->legalBasis->label }}</p>
                        @endisset
                    </div>
                    <div>
                        @if(!empty($project->recht))
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $project->legalBasis->label_additional }}
                            </label>
                            @if($project->recht_additional)
                                <p class="text-gray-900">{{ $project->recht_additional }}</p>
                            @else
                                <p class="text-gray-500 italic">{{ __('project.view.approval.none') }}</p>
                            @endif
                        @endif
                    </div>
                    @if(!empty($project->legalBasis->hint_text))
                        <div class="lg:col-span-2 mt-2">
                            <p class="text-sm text-gray-500 mt-1">{{ $project->legalBasis->hint_text }}</p>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endif

        <!-- Project Details -->
        <flux:card>
            <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('project.view.details.heading') }}</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 mb-1">{{ __('project.view.details.name') }}</label>
                    <p class="text-gray-900 font-medium">
                        @empty($project->name)
                            <x-no-content/>
                        @else
                            {{ $project->name }}
                        @endisset
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('project.view.details.responsible_email') }}
                    </label>
                    @if(empty($project->responsible))
                        <x-no-content/>
                    @else
                        <a href="mailto:{{ $project->responsible }}"
                           class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition-colors"
                        >
                            <x-fas-envelope  class="size-3.5 mr-2"/>
                            {{ $project->responsible }}
                        </a>
                    @endisset
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('project.view.details.org') }}
                    </label>
                    <p class="text-gray-900">
                        @empty($project->org)
                            <x-no-content/>
                        @else
                            {{ $project->org }}
                        @endisset
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('project.view.details.period') }}
                    </label>

                    <p class="text-gray-900">
                        @empty($project->date_start)
                            <x-no-content/>
                        @else
                            <span class="font-medium">{{ __('project.view.details.from') }} </span>
                            {{ $project->date_start?->format('d.m.Y') }}
                            <span class="font-medium mx-2">{{ __('project.view.details.to') }} </span>
                            {{ $project->date_end?->format('d.m.Y') }}
                        @endif
                    </p>
                </div>
                @if($showLink)
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('project.view.details.link') }}
                        </label>
                        <p class="text-gray-500 italic">{{ __('project.view.details.none') }}</p>
                    </div>
                @endif
            </div>
        </flux:card>

        <!-- Budget Table -->
        <flux:card class="overflow-hidden p-0" x-data="budgetTable()">
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loop->iteration }}.
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $post->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 italic">{{ $post->bemerkung }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if($post->budgetItem)
                                    {{ $post->budgetItem->titel_name  }} ({{ $post->budgetItem->titel_nr  }})
                                @endif
                            </td>
                            <td @class(["px-6 py-4 whitespace-nowrap text-sm text-right font-medium",
                                        "text-gray-900" => $post->einnahmen->greaterThan(Money::EUR(0)),
                                        "text-gray-400" => $post->einnahmen->equals(Money::EUR(0)),
                                ])>{{ $post->einnahmen }}</td>
                            <td @class(["px-6 py-4 whitespace-nowrap text-sm text-right font-medium",
                                        "text-gray-900" => $post->ausgaben->greaterThan(Money::EUR(0)),
                                        "text-gray-400" => $post->ausgaben->equals(Money::EUR(0)),
                            ])>
                                {{ $post->ausgaben }}
                            </td>
                            @php $ratio = $post->expendedRatio() @endphp
                            <td @class([
                                "px-6 py-4 whitespace-nowrap text-sm text-right font-medium",
                                "text-gray-400" => $ratio === 0,
                                "text-yellow-600" =>  0 < $ratio && $ratio < 75,
                                "text-green-600" => 75 <= $ratio && $ratio <= 100,
                                "text-red-600" =>  $ratio > 100
                            ])>
                                {{ $post->expendedSum() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($ratio !== 0)
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="w-full bg-gray-200 rounded-full h-2 max-w-[100px]">
                                            <div @class([
                                            "h-2 rounded-full transition-all duration-300",
                                            "bg-green-500" => $ratio >= 75 && $ratio <= 100,
                                            "bg-yellow-500"=>  $ratio < 75,
                                            "bg-red-500" =>  $ratio > 100
                                        ]) style="width: {{ min($ratio,100) }}%"></div>
                                        </div>
                                        <span @class([
                                        "text-xs font-medium",
                                        "text-green-600" => $ratio >= 75 && $ratio <= 100,
                                        "text-yellow-600"=>  $ratio < 75,
                                        "text-red-600" =>  $ratio > 100
                                    ])>
                                        {{ $ratio }}%
                                    </span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot class="bg-white border-t-2 border-gray-200">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right text-sm font-bold text-gray-900">Summe</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                            {{ $totalEinnahmen }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                            {{ $totalAusgaben }}
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </flux:card>

        <!-- Project Description -->
        <flux:card>
            <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('project.view.description.heading') }}</h2>
            @empty($project->beschreibung)
                <x-no-content/>
            @else
                <div class="text-gray-900 whitespace-pre-wrap wrap-break-word">
                    {!! Str::markdown($project->beschreibung) !!}
                </div>
            @endempty
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                @foreach($project->attachments as $attachment)
                    <x-file-card
                        :href="route('project.attachment', [$attachment->id, $attachment->name])"
                        :heading="$attachment->name"
                        :size="$attachment->size"
                        :url="$attachment->url"
                        :icon="$attachment->mime_type"
                    />
                @endforeach
            </div>
        </flux:card>

        <!-- Expenses Section -->
        <flux:card>
            <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('project.view.expenses.heading') }}</h2>
            @if($project->expenses()->count() > 0)
                <div class="space-y-3">
                    @foreach($project->expenses as $expense)
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 hover:bg-gray-100 transition-colors">
                            <a class="flex items-center justify-between gap-5" href="{{ route('legacy.expense', $expense->id) }}">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            A{{ $expense->id }} - {{ $expense->name_suffix }}
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $expense->state }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $expense->zahlung_name }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">{{ __('project.view.expenses.total_in') }}</p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ $expense->totalIn() }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">{{ __('project.view.expenses.total_out') }}</p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ $expense->totalOut() }}
                                    </p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 rounded-lg p-4 text-center text-gray-500">
                    {{ __('project.view.expenses.none') }}
                </div>
            @endif
        </flux:card>
    </div>

    <div class="mt-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <livewire:chat-panel target-type="projekt" :target-id="$project->id" :wire:key="$project->state::class"/>
    </div>

    <!-- Status Change Modal -->
    <flux:modal name="state-modal" class="min-w-96">
        <div>
            <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">{{ __('project.view.state-modal.heading') }}</h3>
            <flux:select wire:model="newState" variant="listbox"
                         placeholder="{{ __('project.view.state-modal.placeholder') }}">
                @foreach($project->state->transitionableStateInstances() as $state)
                    <flux:select.option :value="$state" :disabled="Auth::user()->cannot('transition-to', [$project, $state])">
                        <div class="flex items-center gap-2">
                            <x-dynamic-component :component="$state->iconName()" @class([
                                    "size-4",
                                    "text-zinc-600" => $state->color() === "zinc",
                                    "text-sky-600" => $state->color() === "sky",
                                    "text-yellow-600" => $state->color() === "yellow",
                                    "text-green-600" => $state->color() === "green",
                                    "text-rose-600" => $state->color() === "rose",
                            ])/>{{ $state->label() }}
                        </div>
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 my-4">
                <ul class="list-disc list-inside text-red-600 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="mt-6 flex gap-3">
            <flux:spacer />
            <flux:button x-on:click="$flux.modal('state-modal').close()" variant="ghost">
                {{ __('project.view.state-modal.cancel') }}
            </flux:button>
            <flux:button wire:click="changeState()" variant="primary">
                {{ __('project.view.state-modal.save') }}
            </flux:button>
        </div>
    </flux:modal>

    <!-- Delete Modal -->
    <flux:modal name="delete-modal" class="md:w-96">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <x-fas-triangle-exclamation class="h-6 w-6 text-red-600"/>
                </div>
                <h3 class="text-lg leading-6 font-bold text-gray-900">{{ __('project.view.delete_modal.heading') }}</h3>
            </div>

            <div class="space-y-2">
                <p class="text-sm text-gray-500">{{ __('project.view.delete_modal.intro') }}</p>
                <ul class="text-sm text-gray-500 space-y-1">
                    <li class="flex items-start gap-2">
                        @if($userCanDelete)
                            <x-fas-circle-check class="w-4 h-4 mt-0.5 shrink-0 fill-green-600"/>
                        @else
                            <x-fas-circle-xmark class="w-4 h-4 mt-0.5 shrink-0 fill-red-600"/>
                        @endif
                        <span>{{ __('project.view.delete_modal.conditions.owner') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        @if($deletionAllowed)
                            <x-fas-circle-check class="w-4 h-4 mt-0.5 shrink-0 fill-green-600"/>
                        @else
                            <x-fas-circle-xmark class="w-4 h-4 mt-0.5 shrink-0 fill-red-600"/>
                        @endif
                        <span>{{ __('project.view.delete_modal.conditions.no_expenses') }}</span>
                    </li>
                </ul>
                <p class="text-sm text-gray-500">{{ __('project.view.delete_modal.warning') }}</p>
            </div>

            <div class="flex gap-3">
                <flux:spacer />
                <flux:button x-on:click="$flux.modal('delete-modal').close()" variant="ghost">
                    {{ __('project.view.delete_modal.cancel') }}
                </flux:button>
                <flux:button wire:click="delete()" variant="danger" :disabled="!($userCanDelete && $deletionAllowed)">
                    {{ __('project.view.delete_modal.confirm') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
