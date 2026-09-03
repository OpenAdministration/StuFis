<?php

namespace Tests\Pest\Rules;

use App\Rules\FluxEditorRule;

function fluxEditorFails(string $html): bool
{
    $failed = false;
    (new FluxEditorRule)->validate('beschreibung', $html, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

/*
 * One case per node/mark the default flux:editor has enabled, rendered the way
 * TipTap serialises it. If Flux ever enables another extension, add it here and
 * to FluxEditorRule::ALLOWED_TAGS together.
 */
it('accepts every tag the flux editor can produce', function (string $html): void {
    expect(fluxEditorFails($html))->toBeFalse();
})->with([
    'paragraph' => '<p>Text</p>',
    'hard break' => '<p>eine<br>Zeile</p>',
    // typing "---" in the editor inserts this, no toolbar button involved
    'horizontal rule' => '<p>oben</p><hr><p>unten</p>',
    'blockquote' => '<blockquote><p>Zitat</p></blockquote>',
    'inline code' => '<p>ruf <code>artisan</code> auf</p>',
    'bold' => '<p><strong>fett</strong></p>',
    'italic' => '<p><em>kursiv</em></p>',
    'strike' => '<p><s>gestrichen</s></p>',
    'underline' => '<p><u>unterstrichen</u></p>',
    'highlight' => '<p><mark>markiert</mark></p>',
    'subscript' => '<p>H<sub>2</sub>O</p>',
    'superscript' => '<p>m<sup>2</sup></p>',
    'link' => '<p><a target="_blank" rel="noopener" href="https://example.org">Link</a></p>',
    'bullet list' => '<ul><li><p>eins</p></li></ul>',
    'ordered list' => '<ol><li><p>eins</p></li></ol>',
    'headings' => '<h1>1</h1><h2>2</h2><h3>3</h3><h4>4</h4><h5>5</h5><h6>6</h6>',
    'text align' => '<p style="text-align: center">mittig</p>',
]);

it('rejects tags that must not reach the database', function (string $html): void {
    expect(fluxEditorFails($html))->toBeTrue();
})->with([
    'script' => '<p>ok</p><script>alert(1)</script>',
    'iframe' => '<iframe src="https://example.org"></iframe>',
    'style' => '<style>body{display:none}</style>',
    'img' => '<p><img src="x"></p>',
    'form' => '<form action="/"><input name="a"></form>',
    // tables are shipped but disabled in flux:editor
    'table' => '<table><tr><td>a</td></tr></table>',
    // code blocks are reachable in the editor but deliberately not accepted
    'code block' => '<pre><code>composer fix</code></pre>',
]);
