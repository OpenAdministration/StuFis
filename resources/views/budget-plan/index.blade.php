<x-layout>
    <div class="sm:mx-8 mt-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">{{ __('budget-plan.index-headline') }}</h1>
                <p class="mt-2 text-sm text-gray-700">{{ __('budget-plan.index-headline-sub') }}</p>
            </div>
        </div>
        <div class="mt-8 flex flex-col overflow-hidden bg-white shadow sm:rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
                @foreach($plans as $plan)
                    <li>
                    <a href="{{ route('budget-plan.show', ['id' => $plan->id]) }}" class="block group hover:bg-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center">
                                    <p class="truncate text-sm font-medium text-indigo-600">
                                        {{ $plan->organisation }}
                                    </p>
                                    <div class="ml-2 flex flex-shrink-0">
                                        <p class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                            {{ $plan->state }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 sm:flex sm:justify-between">
                                    <div class="sm:flex">
                                        <p class="flex items-center text-sm text-gray-500">
                                            <x-heroicon-m-calendar class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400" />
                                            <x-date :date="$plan->start_date" format="M y"/>
                                            <span class="px-1">-</span>
                                            <x-date :date="$plan->end_date" format="M y"/>
                                        </p>
                                        <!-- follows: class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6" -->
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 pr-4">
                                <x-heroicon-s-chevron-right class="h-5 w-5 text-gray-400 group-hover:text-gray-600" />
                            </div>
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-layout>
