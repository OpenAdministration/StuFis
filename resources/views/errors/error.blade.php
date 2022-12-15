<x-layout>
    <div class="min-h-full pt-16 pb-12 flex flex-col bg-white">
        <section class="flex-grow flex flex-col justify-center max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex-shrink-0 flex justify-center">
                <a href="/" class="inline-flex">
                    <x-logo class="h-12 w-auto text-indigo-600"/>
                </a>
            </div>
            <div class="py-16">
                <div class="text-center">
                    <p class="text-base font-semibold text-indigo-600">
                        {{ $e->getCode() }}
                    </p>
                    <h1 class="mt-2 text-4xl font-bold text-gray-900 tracking-tight sm:text-5xl sm:tracking-tight">
                        Ein Fehler ist aufgetreten :(
                    </h1>
                    <p class="mt-2 text-base text-gray-500">
                        {{ $e->getMessage() }}
                    </p>
                    <div class="mt-6">
                        <a href="#" class="text-base font-medium text-indigo-600 hover:text-indigo-500">
                            Zurück zur Startseite<span aria-hidden="true"> &rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
        <footer class="flex-shrink-0 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex justify-center space-x-4">
                <a href="#" class="text-sm font-medium text-gray-500 hover:text-gray-600">Fehler melden</a>
                <span class="inline-block border-l border-gray-300" aria-hidden="true"></span>
                <a href="#" class="text-sm font-medium text-gray-500 hover:text-gray-600">Status</a>
                <span class="inline-block border-l border-gray-300" aria-hidden="true"></span>
                <a href="#" class="text-sm font-medium text-gray-500 hover:text-gray-600">Twitter</a>
            </nav>
        </footer>
    </div>


</x-layout>
