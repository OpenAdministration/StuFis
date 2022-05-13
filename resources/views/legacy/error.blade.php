<x-layout title="Error">
    <x-slot name="title">
        Fehler
    </x-slot>
    {{ $e->getMessage() }}
</x-layout>

