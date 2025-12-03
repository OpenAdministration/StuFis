@php use App\Models\Enums\ChatMessageType; @endphp
<div class="bg-white rounded-2xl shadow-accent border border-gray-200 p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Nachrichten</h2>

    <ul role="list" class="space-y-6">
        @foreach($messages as $message)
            @if($message->type === ChatMessageType::SYSTEM)
                <li class="relative flex gap-x-4">
                    <div class="absolute top-0 -bottom-6 left-1 flex w-6 justify-center">
                        <div class="w-px bg-gray-200"></div>
                    </div>
                    <div class="ml-1 relative flex size-6 flex-none items-center justify-center bg-white">
                        <div class="size-1.5 rounded-full bg-gray-100 ring ring-gray-300"></div>
                    </div>
                    <p class="flex-auto py-0.5 text-xs/5 text-gray-500">
                        <span class="font-medium text-gray-900">Statuswechsel</span> {{ $message->text }}</p>
                    <time datetime="2023-01-23T10:32" class="flex-none py-0.5 text-xs/5 text-gray-500">7d ago</time>
                </li>
            @elseif($message->type === ChatMessageType::PUBLIC)
                <li class="relative flex gap-x-4">
                    <div class="absolute top-0 -bottom-6 left-1 flex w-6 justify-center">
                        <div class="w-px bg-gray-200"></div>
                    </div>

                    <div class="mt-3 relative">
                        <x-profile-pic :user="$message->user" size="sm"/>
                    </div>
                    <div class="flex-auto rounded-md p-3 ring-1 ring-gray-200 ring-inset">
                        <div class="flex justify-between gap-x-4">
                            <div class="py-0.5 text-xs/5 text-gray-500"><span
                                    class="font-medium text-gray-900">{{ $message->user->name }}</span> commented
                            </div>
                            <time datetime="{{ $message->timestamp }}" class="flex-none py-0.5 text-xs/5 text-gray-500">{{ $message->timestamp->diffForHumans() }}</time>
                        </div>
                        <p class="text-sm/6 text-gray-500">{{ $message->text }}</p>
                    </div>
                </li>
            @elseif($message->type === ChatMessageType::SUPPORT)
                tbd
            @elseif($message->type === ChatMessageType::FINANCE)
                tbd
            @endif
        @endforeach


    </ul>

    <!-- New comment form -->
    <div class="mt-6 flex gap-x-3">

        <div class="-ml-1 mt-3">
            <x-profile-pic/>
        </div>
        <form action="#" class="relative flex-auto">
            <div class="overflow-hidden">
                <flux:editor wire:model="content"/>
            </div>

            <div class="py-2 pr-2 pl-3 bottom-0 absolute right-0">
                <flux:button wire:click="save" variant="primary" color="indigo" icon="paper-airplane"></flux:button>
            </div>
        </form>
    </div>


</div>
