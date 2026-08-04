@props([
    'disabled' => false,
])

@php
    // Both branches render the error: a disabled field can still carry one (a Posten holding
    // an income *and* an expense disables both inputs), and hiding the reason leaves the user
    // with a save that fails and nothing to act on.
    $errorName = $attributes->thatStartWith('wire:model')->first();
@endphp

@if(!$disabled)
    <flux:input {{ $attributes->merge(['class:input' => 'text-right']) }} />
@else
    <flux:input disabled readonly variant="filled" {{ $attributes->merge(['class:input' => 'text-right text-black!']) }}/>
@endif
<flux:error :name="$errorName" />
