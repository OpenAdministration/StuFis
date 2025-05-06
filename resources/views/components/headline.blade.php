@props([
    'level' => 'h1', // TODO: different styles?
    'headline',
    'subText',
])

<div class="sm:flex sm:items-center mb-3">
    <div class="sm:flex-auto max-w-(--breakpoint-lg)">
        <h2 class="text-xl font-semibold text-gray-900">{{ $headline }}</h2>
        <p class="mt-2 text-sm text-gray-700">{{ $subText }}</p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        @isset($button)
            <a href="{{ $button->attributes->get('href') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-xs hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                {{ $button }}
            </a>
        @endisset
    </div>
</div>
