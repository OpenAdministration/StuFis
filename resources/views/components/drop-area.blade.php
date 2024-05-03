
@php
    $wireModel = $attributes->thatStartWith('wire:model')->first()
    // TODO: Loading Indicator
@endphp
<div class="my-2">
    <div class="flex items-center justify-center w-full">
        <label for="dropzone-file" @class([
        "flex flex-col items-center justify-center w-full border-2 border-gray-300  rounded-lg cursor-pointer dark:border-gray-600 dark:hover:border-gray-500",
        "h-64 border-dashed dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100   bg-gray-50 dark:hover:bg-gray-600" => empty($$wireModel),
        "bg-green-100 h-16 border-green-200" => !empty($$wireModel),
    ])>
            <div @class([
                "flex flex-col items-center justify-center pt-5 pb-6",
                "hidden" => !empty($$wireModel),
            ])>
                <x-fas-upload class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400"/>
                {{ $slot }}
            </div>
            <input id="dropzone-file" type="file" wire:model.live="{{ $wireModel }}" />
        </label>
    </div>
    @error($wireModel) <span class="error">{{ $message }}</span> @enderror

</div>
