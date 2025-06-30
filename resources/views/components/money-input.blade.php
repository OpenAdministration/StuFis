@props([
    'model',
    'value' => 0,
])

<flux:input x-data="{ money : new Intl.NumberFormat('de-DE', {minimumFractionDigits:2}).format({{ $value }} / 100) }"
    class:input="text-right" x-model="money" x-mask:dynamic="$money($input, ',')"
    x-on:blur="$wire.set('{{ $attributes->wire('model')->value() }}', money.replace(',','.') * 100)"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
/>





