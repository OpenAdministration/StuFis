<x-layout>
    <div class="mt-8 sm:mx-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">{{ __('konto.manual-headline') }}</h1>
                <p class="mt-2 text-sm text-gray-700">{{ __('konto.manual-headline-sub') }}</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="#" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                    <x-heroicon-o-plus class="-ml-0.5 mr-2 h-4 w-4"/>
                    {{ __('konto.csv-button-new-konto') }}
                </a>
            </div>
        </div>
        {{-- @if($data->isEmpty()) --}}
            <!--a href="{{ route('budget-plan.create') }}" class="relative block w-full p-12 mt-8 text-center border-2 border-gray-300 border-dashed rounded-lg group hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <x-heroicon-o-table-cells stroke-width="1" class="w-12 h-12 mx-auto text-gray-400 group-hover:text-gray-600" />
                <span class="block mt-2 text-sm font-medium text-gray-700 group-hover:text-black">
                    {{ __('budget-plan.index-no-plans') }}
                </span>
            </a-->

            <form method="POST" enctype="multipart/form-data" id="upload" action="{{ url('konto/import/manual/upload') }}" >
                @csrf
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="form-group">
                            <input type="file" name="file" placeholder="{{ __('konto.csv-button-choose-file') }}" id="file" accept=".csv">
                              @error('file')
                              <div class="mt-1 mb-1 alert alert-danger">{{ $message }}</div>
                              @enderror
                        </div>
                    </div>

                    <div class="py-4">
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto" id="submit">{{ __('konto.manual-button-submit') }}</button>
                    </div>
                    <br>
                    <div class="col-md-6 offset-md-3">
                                  @if(session('status'))
                                    <div class="alert alert-success">
                                        {{ session('status') }}
                                    </div>
                                  @endif

                      </div>
                </div>
            </form>

        {{-- @else
            <div class="flex flex-col mt-8 overflow-hidden bg-white shadow sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($plans as $plan)
                        <li>
                            <a href="{{ route('budget-plan.show', ['plan_id' => $plan->id]) }}" class="block group hover:bg-gray-100">
                                <div class="flex items-center justify-between">
                                    <div class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-sm font-medium text-indigo-600 truncate">
                                                {{ $plan->organisation }}
                                            </p>
                                            <div class="flex flex-shrink-0 ml-2">
                                                <p class="inline-flex px-2 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full">
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
                                                <!-- follows: class="flex items-center mt-2 text-sm text-gray-500 sm:mt-0 sm:ml-6" -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 pr-4">
                                        <x-heroicon-s-chevron-right class="w-5 h-5 text-gray-400 group-hover:text-gray-600" />
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif --}}
    </div>
</x-layout>
