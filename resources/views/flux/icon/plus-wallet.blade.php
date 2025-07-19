{{-- Credit: Lucide (https://lucide.dev) --}}

@props([
    'variant' => 'outline',
])

@php
if ($variant === 'solid') {
    throw new \Exception('The "solid" variant is not supported in Lucide.');
}

$classes = Flux::classes('shrink-0')
    ->add(match($variant) {
        'outline' => '[:where(&)]:size-6',
        'solid' => '[:where(&)]:size-6',
        'mini' => '[:where(&)]:size-5',
        'micro' => '[:where(&)]:size-4',
    });

$strokeWidth = match ($variant) {
    'outline' => 2,
    'mini' => 2.25,
    'micro' => 2.5,
};
@endphp


{{--
<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    data-slot="icon"
>
</svg> --}}

<div>
    <div {{ $attributes->class($classes) }} some-attribute>
        {{-- Haupt-Icon --}}
        <x-fas-wallet class="w-5 h-5"/>

        {{-- Plus-Overlay rechts unten --}}
        <span @class([
            "absolute bottom-0.5 bg-white rounded-full p-0.5 flex items-center justify-center",
            "left-0.5" => true,
        ])>
        <x-fas-plus class="w-3 h-3"/>
    </span>
    </div>
</div>
