
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale())}}" class="h-full bg-gray-50">
<head>
    <title>{{ $title ?? 'StuRa Finanzen' }}</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    @livewireStyles
    @livewireScripts
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="h-full overflow-hidden">
<div x-data="{ mobileMenu : false }" class="h-full flex">
    <!-- Narrow sidebar -->
    <div class="hidden w-28 bg-indigo-700 overflow-y-auto md:block">
        <div class="w-full py-6 flex flex-col items-center">
            <div class="flex-shrink-0 flex items-center">
                <x-logo class="h-16 w-auto"/>
            </div>
            <div class="flex-1 mt-6 w-full px-2 space-y-1">
                @foreach($navigationSkeleton as $item)
                    <!-- Current: "bg-indigo-800 text-white", Default: "text-indigo-100 hover:bg-indigo-800 hover:text-white" -->
                    <a href="{{ route(...$item['route']) }}" class="text-indigo-100 hover:bg-indigo-800 hover:text-white group w-full p-3 rounded-md flex flex-col items-center text-xs font-medium">
                        <x-dynamic-component :component="$item['icon']" class="text-indigo-300 group-hover:text-white w-6 h-6"></x-dynamic-component>
                        <span class="mt-2">{{ $item['text'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    <!--
      Mobile menu

      Off-canvas menu for mobile, show/hide based on off-canvas menu state.
    -->
    <div x-show="mobileMenu" class="relative z-20 md:hidden" role="dialog" aria-modal="true">
        <div x-show="mobileMenu"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>

        <div class="fixed inset-0 z-40 flex">
            <div x-show="mobileMenu"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 @click.outside="mobileMenu = false"
                 class="relative max-w-xs w-full bg-indigo-700 pt-5 pb-4 flex-1 flex flex-col">
                <div x-show="mobileMenu"
                     x-transition:enter="ease-in-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in-out duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute top-1 right-0 -mr-14 p-1">
                    <button @click="mobileMenu=false" type="button" class="h-12 w-12 rounded-full flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-white">
                        <x-heroicon-o-x-mark class="h-6 w-6 text-white"/>
                        <span class="sr-only">Close sidebar</span>
                    </button>
                </div>

                <div class="flex-shrink-0 px-4 flex items-center">
                    <!-- Mobile -->
                    <x-logo class="h-16 w-auto"/>
                </div>
                <div class="mt-5 flex-1 h-0 px-2 overflow-y-auto">
                    <nav class="h-full flex flex-col">
                        <div class="space-y-1">
                            @foreach($navigationSkeleton as $item)
                                <!-- Current: "bg-indigo-800 text-white", Default: "text-indigo-100 hover:bg-indigo-800 hover:text-white" -->
                                <a href="{{ route(...$item['route']) }}" class="text-indigo-100 hover:bg-indigo-800 hover:text-white group py-2 px-3 rounded-md flex items-center text-sm font-medium">
                                    <x-dynamic-component :component="$item['icon']" class="text-indigo-300 group-hover:text-white mr-3 h-6 w-6"></x-dynamic-component>
                                    <span>{{ $item['text'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </nav>
                </div>
            </div>

            <div class="flex-shrink-0 w-14" aria-hidden="true">
                <!-- Dummy element to force sidebar to shrink to fit close icon -->
            </div>
        </div>
    </div>

    <!-- Content area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="w-full">
            <div class="relative z-10 flex-shrink-0 h-16 bg-white border-b border-gray-200 shadow-sm flex">
                <button @click="mobileMenu = true" type="button" class="border-r border-gray-200 px-4 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 md:hidden" x-transition>
                    <span class="sr-only">Open sidebar</span>
                    <!-- Heroicon name: outline/menu-alt-2 -->
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </button>
                <div class="flex-1 flex justify-between px-4 sm:px-6">
                    <div class="flex-1 flex">
                        <!--
                        <form class="w-full flex md:ml-0" action="#" method="GET">
                            <label for="search-field" class="sr-only">Search all files</label>
                            <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center">
                                    <x-heroicon-s-magnifying-glass class="flex-shrink-0 h-5 w-5"/>
                                </div>
                                <input name="search-field" id="search-field" class="h-full w-full border-transparent py-2 pl-8 pr-3 text-base text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:placeholder-gray-400" placeholder="Search" type="search">

                            </div>
                        </form>-->
                    </div>
                    <div class="ml-2 flex items-center space-x-4 sm:ml-6 sm:space-x-6">
                        <!-- Profile dropdown -->
                        <div x-data="{ profile: false }" class="relative flex-shrink-0" >
                            <div>
                                <button x-on:click="profile = ! profile" type="button" class="bg-white rounded-full flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open user menu</span>
                                    <x-profile-pic class="h-8 w-8 rounded-full text-indigo-600"/>
                                </button>
                            </div>

                            <div x-show="profile" x-on:click.outside="profile = false"
                                 x-transform:enter="transition ease-out duration-100"
                                 x-transform:enter-start="transform opacity-0 scale-95"
                                 x-transform:enter-end="transform opacity-100 scale-100"
                                 x-transform:leave="transition ease-in duration-75"
                                 x-transform:leave-start="transform opacity-100 scale-100"
                                 x-transform:leave-end="transform opacity-0 scale-95"

                                 class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu"
                                 aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                                    <a class="block px-4 py-2 text-black">{{ Auth::user()->name }}</a>
                                @foreach($profileSkeleton as $idx => $item)
                                    <!-- Active: "bg-gray-100", Not Active: "" -->
                                    <a href="{{ $item['link'] ?? route(...$item['route']) }}" class="block px-4 py-2 text-sm text-gray-700" role="menuitem" tabindex="-1" id="user-menu-item-{{ $idx }}">
                                        {{ $item['text'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <a href="{{ route('new-project') }}" id='new-project-button' alt="{{ __('New Project') }}" class="flex bg-indigo-600 p-1 rounded-full items-center justify-center text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <x-heroicon-o-plus-small class="h-6 w-6"/>
                            <span class="sr-only">Neues Projekt</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="relative z-0 flex flex-1 overflow-hidden">
            <main class="relative z-0 flex-1 overflow-y-auto focus:outline-none">
                <!-- Start main area-->
                {{ $slot }}
                <!-- End main area -->
            </main>
            @isset($sideColumn)
            <aside class="relative hidden w-1/3 flex-shrink-0 overflow-y-auto border-l border-gray-200 xl:flex xl:flex-col">
                <!-- Start secondary column (hidden on smaller screens) -->
                <div class="absolute inset-0 py-6 px-4 sm:px-6 lg:px-8">
                    <div class="h-full rounded-lg border-2 border-dashed border-gray-200"></div>
                </div>
                <!-- End secondary column -->
            </aside>
            @endisset
        </div>
    </div>
</div>
</body>
@if(!empty($legacyContent))
    <script type="text/javascript" src="{{ asset('/js/legacy.js') }}"></script>
@endif
</html>
