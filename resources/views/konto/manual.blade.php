<x-layout>
    <div class="mt-8 sm:mx-8">
        @if(count($errors)>0)
            <div class="alert alert-danger">
                <ul>

                    @foreach($errors->all() as $error)

                        <li>{{$error}}</li>

                    @endforeach
                </ul>
            </div>
        @endif
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
        @if(!$data)
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

        @else
            <div class="flex flex-col mt-8 overflow-hidden bg-white shadow sm:rounded-md">
                <form method="POST" enctype="multipart/form-data" id="assign" action="{{ url('konto/import/manual/assign') }}" >
                    @csrf
                    <div class="row">
                        <ul role="list" class="divide-y divide-gray-200">
                            @foreach($mapping as $attr => $label)
                                <li>
                                    <div>
                                        <label for="{{ $attr }}" class="block text-sm font-medium leading-6 text-gray-900">{{ $label }}</label>
                                        <select id="{{ $attr }}" name="{{ $attr }}" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            @foreach($data[0] as $csvheader => $value)
                                                <option>{{ $csvheader }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </li>
                            @endforeach
                        </ul>

                        <div class="py-4">
                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto" id="submit-assign">{{ __('konto.manual-button-assign') }}</button>
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
            </div>
        @endif
    </div>
</x-layout>
