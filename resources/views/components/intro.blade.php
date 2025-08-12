@props([
    'headline' => $slot, // backwards compatible
    'subHeadline' => '', // used as attribute string
    'button', // used as string text or a slot
    'level' => 1
])

<div class="sm:flex sm:items-center mb-3">
    <div class="sm:flex-auto max-w-(--breakpoint-lg)">
        <flux:heading :level="$level" size="{{ $level === 1 ? 'xl' : 'lg' }}">{{ $headline }}</flux:heading>
        @if($subHeadline)
            <flux:text class="mt-1">{{ $subHeadline }}</flux:text>
        @endif
    </div>
    @isset($button)
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        @if(is_string($button))
            <flux:button variant="primary"> {{ $button }}</flux:button>
        @else
            {{ $button }}
        @endif
    </div>
    @endisset
</div>
