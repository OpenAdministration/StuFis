<div class="bg-white rounded-2xl shadow-accent border border-gray-200 p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('chat.heading') }}</h2>

    <ul role="list" class="space-y-6 ">
        @foreach($messages as $message)
            @if($message->type === App\Models\Enums\ChatMessageType::SYSTEM)
                <li class="relative flex gap-x-4">
                    <div class="absolute top-0 -bottom-6 left-1 flex w-6 justify-center">
                        <div class="w-px bg-gray-200"></div>
                    </div>
                    <div class="ml-1 relative flex size-6 flex-none items-center justify-center bg-white">
                        <div class="size-1.5 rounded-full bg-gray-100 ring ring-gray-300"></div>
                    </div>
                    @php($author = $message->user?->name ?? ($message->creator_alias ?: null))
                    <p class="flex-auto py-0.5 text-xs/5 text-gray-500">
                        <span class="font-medium text-gray-900">{{ $message->text }}</span>@if($author) <span class="text-gray-400">{{ __('chat.by') }}</span> <span class="text-gray-700">{{ $author }}</span>@endif</p>
                    <time datetime="{{ $message->timestamp }}" class="flex-none py-0.5 text-xs/5 text-gray-500">
                        {{ $message->timestamp->diffForHumans() }}
                    </time>
                </li>
            @elseif($message->type === App\Models\Enums\ChatMessageType::PUBLIC)
                    <li class="relative flex gap-x-4">
                        <div class="absolute top-0 -bottom-6 left-1 flex w-6 justify-center">
                            <div class="w-px bg-gray-200"></div>
                        </div>

                        <div class="mt-3 relative">
                            <x-profile-pic :user="$message->user" size="sm"/>
                        </div>
                        <div class="min-w-0 flex-auto rounded-md p-3 ring-1 ring-gray-200 ring-inset">
                            <div class="flex justify-between gap-x-4">
                                <div class="min-w-0 py-0.5 text-xs/5 text-gray-500"><span
                                        class="font-medium text-gray-900">{{ $message->user?->name ?? $message->creator_alias ?? __('chat.unknown') }}</span> {{ __('chat.commented') }}
                                </div>
                                <time datetime="{{ $message->timestamp }}" class="flex-none py-0.5 text-xs/5 text-gray-500">{{ $message->timestamp->diffForHumans() }}</time>
                            </div>
                            <div class="wrap-break-word text-sm/6">{!! $message->text !!}</div>
                        </div>
                    </li>
            @elseif($message->type === App\Models\Enums\ChatMessageType::SUPPORT)
            @elseif($message->type === App\Models\Enums\ChatMessageType::FINANCE)
            @endif
        @endforeach


    </ul>

    <!-- New comment form -->
    <div class="mt-6 flex gap-x-3">
        <div class="-ml-1 mt-3">
            <x-profile-pic/>
        </div>
        <div class="relative flex-auto min-w-0">
            <div class="min-w-0 max-w-full overflow-hidden">
                <flux:editor wire:model="content" class="w-full max-w-full wrap-break-word"/>
            </div>
            <div class="py-2 pr-2 pl-3 bottom-0 absolute right-0">
                <flux:button wire:click="save" variant="primary" color="indigo" icon="paper-airplane"></flux:button>
            </div>
        </div>
    </div>
    @error('content')
    <div class="ml-14 mt-2 text-sm text-red-600">{{ $message }}</div>
    @enderror


</div>
