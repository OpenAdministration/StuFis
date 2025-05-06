@props([
    'status' => session('status', 'success'),
])
@php
    $isSuccess = ($status === 'success');
    $isWarning = ($status === 'warning');
    $isError = ($status === 'error')
@endphp

@if (session()->has('message'))
    <div @class([
        "rounded-md",
        "p-4",
        "mb-6",
        "bg-green-100" => $isSuccess,
        "bg-yellow-100" => $isWarning,
        "bg-red-100" => $isError,
    ]) x-data="{ showMessage: true }" x-show="showMessage">
        <div class="flex">
            <div class="shrink-0">
                @if($isSuccess)
                    <x-fas-award class="h-5 w-5 text-green-400"/>
                @elseif($isWarning)
                    <x-fas-triangle-exclamation class="h-5 w-5 text-yellow-500"/>
                @elseif($isError)
                    <x-fas-poo-storm class="h-5 w-5 text-red-400"/>
                @endif
            </div>
            <div class="ml-3 grow">
                <p @class([
                    "text-sm",
                    "font-medium",
                    "text-green-800" => $isSuccess,
                    "text-yellow-800" => $isWarning,
                    "text-red-800" => $isWarning,
                ])>{{ session('message') }}</p>
            </div>
            <button class="shrink-0" x-on:click="showMessage = false">
                <x-fas-x class="text-green-800 hover:text-green-900 h-5 w-5 opacity-75 p-1"></x-fas-x>
            </button>
        </div>
    </div>
@endif
