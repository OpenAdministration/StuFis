@props([
    'status' => session('message.type', default: 'success'),
])

@php
    $styles = [
        'success' => [
            'bg' => 'bg-green-100',
            'text' => 'text-green-800',
            'icon' => 'fas-award',
            'iconColor' => 'text-green-400'
        ],
        'warning' => [
            'bg' => 'bg-yellow-100',
            'text' => 'text-yellow-800',
            'icon' => 'fas-triangle-exclamation',
            'iconColor' => 'text-yellow-500'
        ],
        'error' => [
            'bg' => 'bg-red-100',
            'text' => 'text-red-800',
            'icon' => 'fas-poo-storm',
            'iconColor' => 'text-red-400'
        ],
        'info' => [
            'bg' => 'bg-cyan-100',
            'text' => 'text-cyan-800',
            'icon' => 'fas-info-circle',
            'iconColor' => 'text-cyan-400'
        ],
        'update' => [
            'icon' => 'fas-champagne-glasses',
            'bg' => 'bg-teal-100',
            'text' => 'text-teal-800',
            'iconColor' => 'text-teal-400'
        ],
    ];

    $currentStyle = $styles[$status] ?? $styles['success'];
@endphp

<div>
    @if (session()->has('message'))
        <div class="p-4 mb-6 {{ $currentStyle['bg'] }} rounded">
            <div class="flex">
                <div class="shrink-0">
                    <x-dynamic-component
                        :component="$currentStyle['icon']"
                        class="h-5 w-5 {{ $currentStyle['iconColor'] }}"
                    />
                </div>
                <div class="ml-3 grow">
                    <p class="text-sm font-medium {{ $currentStyle['text'] }}">
                        {{ session('message.text') }}
                    </p>
                </div>
                <button class="shrink-0 cursor-pointer" wire:click="closeNotification()">
                    <x-fas-x class="text-green-800 hover:text-green-900 h-5 w-5 opacity-75 p-1"></x-fas-x>
                </button>
            </div>
        </div>
    @endif
</div>
