{{--
    Flux wires the "hr" command in editor.js but ships no component for it, so
    without this button the only way to get a horizontal rule is typing "---".
    flux:editor.button renders a bare <button>, so data-editor passes through to
    the toolbar's click handler.
--}}
@blaze(fold: true, safe: ['kbd'])

@props([
    'kbd' => null,
])

<flux:tooltip content="{{ __('Horizontal rule') }}" :$kbd class="contents">
    <flux:editor.button data-editor="hr">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12h18"/></svg>
    </flux:editor.button>
</flux:tooltip>
