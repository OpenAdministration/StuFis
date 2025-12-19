@blaze

@props([
    'icon' => 'document',
    'invalid' => false,
    'actions' => null,
    'heading' => null,
    'inline' => false,
    'image' => null,
    'text' => null,
    'size' => null,
    'href' => null,
])

@php
    $classes = Flux::classes()
        ->add('overflow-hidden') // Overflow hidden is here to prevent the button from growing when selected text is too long.
        ->add('flex items-start')
        ->add('shadow-xs')
        ->add('bg-white hover:bg-gray-100 dark:bg-white/10 dark:disabled:bg-white/[7%]')
        // Make the placeholder match the text color of standard input placeholders...
        ->add('disabled:shadow-none')
        ->add('min-h-10 text-base sm:text-sm rounded-lg block w-full')
        ->add($invalid
            ? 'border border-red-500'
            : 'border border-zinc-200 border-b-zinc-300/80 dark:border-white/10'
        )
        ;

    $figureWrapperClasses = Flux::classes()
        ->add('p-[calc(0.75rem-1px)] flex items-baseline')
        ->add('[&:has([data-slot=image])]:p-[calc(0.5rem-1px)]')
        ;

    $imageWrapperClasses = Flux::classes()
        ->add('relative mr-1 size-11 rounded-sm overflow-hidden')
        ->add([
            'after:absolute after:inset-0 after:inset-ring-[1px] after:inset-ring-black/7 dark:after:inset-ring-white/10',
            'after:rounded-sm',
        ])
        ;

    if ($size) {
        if ($size < 1024) {
            $text = round($size) . ' B';
        } elseif ($size < 1024 * 1024) {
            $text = round($size / 1024) . ' KB';
        } elseif ($size < 1024 * 1024 * 1024) {
            $text = round($size / 1024 / 1024) . ' MB';
        } else {
            $text = round($size / 1024 / 1024 / 1024) . ' GB';
        }
    }

    $iconVariant = $text ? 'solid' : 'micro';
@endphp
<div {{ $attributes->class($classes) }}>
    <a href="{{ $href }}" target="_blank" class="cursor-pointer flex-1 flex items-start">
        <div class="{{ $figureWrapperClasses }}">
            @if(str_contains($icon, 'pdf' ))
                <x-fas-file-pdf class="size-8 text-red-400 [&:has(+[data-slot=image])]:hidden"/>
            @elseif(str_contains($icon, 'xls') || str_contains($icon, 'opendocument.spreadsheet'))
                <x-fas-file-excel class="size-8 text-green-600 [&:has(+[data-slot=image])]:hidden"/>
            @else
                <flux:icon name="{{ $icon }}" variant="{{ $iconVariant }}"
                           class="text-zinc-400 [&:has(+[data-slot=image])]:hidden"/>
            @endif

            <?php if ($image): ?>
            <div class="{{ $imageWrapperClasses }}" data-slot="image">
                <img class="h-full w-full object-cover" src="{{ $image }}" alt="">
            </div>
            <?php endif; ?>
        </div>

        <div class="flex-1 overflow-hidden py-[calc(0.75rem-3px)] me-3 flex flex-col justify-center gap-1"
             data-slot="content">
            <?php if ($heading): ?>
            <div
                class="text-sm font-medium text-zinc-700 dark:text-white/80 whitespace-nowrap overflow-hidden text-ellipsis">{{ $heading }}</div>
            <?php endif; ?>

            <?php if ($text): ?>
            <div class="text-xs text-zinc-500">{{ $text }}</div>
            <?php endif; ?>
        </div>
    </a>

    <?php if ($actions): ?>
    <div {{ $actions->attributes->class([
        'p-[calc(0.25rem-1px)]',
        'flex-shrink-0 self-start flex h-full items-center gap-2'
    ]) }} data-slot="actions">
        {{ $actions }}
    </div>
    <?php endif; ?>
</div>
