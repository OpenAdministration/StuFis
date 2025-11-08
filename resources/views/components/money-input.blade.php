@props([
    'disabled' => false,
])

@if(!$disabled)
    <flux:input {{ $attributes->merge(['class:input' => 'text-right']) }} />
@else
    <flux:input disabled readonly variant="filled" {{ $attributes->merge(['class:input' => 'text-right text-black!']) }}/>
@endif
