@props([
    'href',
])
<x-PTF15.antrag.table.row>
    <x-PTF15.antrag.table.cell />
    <x-PTF15.antrag.table.cell>
        <a href="{{ $href }}" class="text-lg hover:text-indigo-600 hover:underline"><x-fas-plus class="inline mx-2 -mt-1"/>{{ $slot }}</a>
    </x-PTF15.antrag.table.cell>
    <x-PTF15.antrag.table.cell />
    <x-PTF15.antrag.table.cell />
</x-PTF15.antrag.table.row>
