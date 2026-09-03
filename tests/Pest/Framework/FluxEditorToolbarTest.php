<?php

namespace Tests\Pest\Framework;

use Illuminate\Support\Facades\Blade;

/*
 * The toolbar string is duplicated in the two views that use flux:editor. These
 * tests guard the two ways it can silently break: a typo in an item name (Flux
 * renders nothing for an unknown item rather than erroring), and our own
 * editor.hr component going missing -- Flux ships no stub for it.
 */
const EDITOR_TOOLBAR = 'heading | bold italic underline strike highlight subscript superscript | bullet ordered blockquote hr | link | align ~ undo redo';

function renderEditorToolbar(): string
{
    return Blade::render('<flux:editor :toolbar="$bar" />', ['bar' => EDITOR_TOOLBAR]);
}

it('renders a control for every item in the toolbar string', function (string $item): void {
    expect(renderEditorToolbar())->toContain('data-editor="'.$item.'"');
})->with([
    'heading', 'bold', 'italic', 'underline', 'strike', 'highlight',
    'subscript', 'superscript', 'bullet', 'ordered', 'blockquote', 'hr',
    'link', 'align', 'undo', 'redo',
]);

it('labels the toolbar in german', function (string $label): void {
    expect(renderEditorToolbar())->toContain($label);
})->with([
    'Fett', 'Kursiv', 'Unterstrichen', 'Durchgestrichen', 'Hervorheben',
    'Tiefgestellt', 'Hochgestellt', 'Aufzählung', 'Nummerierte Liste', 'Zitat',
    'Trennlinie', 'Link einfügen', 'Ausrichtung', 'Rückgängig', 'Wiederherstellen',
    'Formatvorlagen', 'Fließtext', 'Überschrift 1',
]);

it('uses the same toolbar in both editors', function (string $view): void {
    expect(file_get_contents(resource_path($view)))->toContain('toolbar="'.EDITOR_TOOLBAR.'"');
})->with([
    'views/livewire/chat-panel.blade.php',
    'views/pages/project/⚡edit-project/edit-project.blade.php',
]);
