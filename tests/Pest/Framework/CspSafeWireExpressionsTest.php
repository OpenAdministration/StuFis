<?php

use Symfony\Component\Finder\Finder;

/**
 * Guards against a whole class of silently-dead buttons under our enforcing CSP.
 *
 * The app ships Livewire's CSP-safe bundle (resources/js/app.js imports
 * `livewire.csp.esm`). That build cannot use `new Function`, so instead of compiling
 * `wire:click="foo(1)"` into JS it rewrites the expression to `$wire.foo(1)` and feeds it to a
 * hand-written tokenizer/parser (Alpine's CSP evaluator).
 *
 * That tokenizer classifies a fixed set of words as KEYWORD *regardless of position* — including
 * after a dot. `parseMember()` then does `consume("IDENTIFIER")` on the property name and throws
 * `Expected IDENTIFIER but got KEYWORD "delete"`. Livewire catches that in
 * `evaluateActionExpression()` and only `console.warn`s it, so a `wire:click="delete(1)"` button
 * looks completely inert: no request, no error toast, nothing.
 *
 * So: no Livewire action may be *named* like a JS keyword. Rename the component method
 * (`deleteItem()`, `deleteProject()`, …) rather than working around it in the view.
 */
/** The keyword list from the CSP tokenizer (`readIdentifierOrKeyword` in livewire.csp.esm.js). */
const CSP_RESERVED_WORDS = ['true', 'false', 'null', 'undefined', 'new', 'typeof', 'void', 'delete', 'in', 'instanceof'];

/**
 * Directives Livewire handles itself instead of turning them into an Alpine `x-on` handler
 * (the skip list at the top of `js/directives/wire-wildcard.js`).
 */
const NON_ACTION_WIRE_DIRECTIVES = [
    'snapshot', 'effects', 'model', 'init', 'loading', 'poll', 'ignore', 'id', 'data',
    'key', 'target', 'dirty', 'sort', 'navigate', 'confirm', 'transition', 'replace',
    'cloak', 'current', 'offline', 'stream', 'text', 'show', 'bind',
];

it('never binds a Livewire action whose name a CSP-safe evaluator would reject', function (): void {
    $offenders = [];

    $files = Finder::create()->files()->in(resource_path('views'))->name('*.blade.php');

    foreach ($files as $file) {
        $contents = $file->getContents();

        // wire:<event>[.modifiers]="<expression>" — double-quoted only, which is what Blade emits.
        preg_match_all('/\bwire:([\w.:-]+)\s*=\s*"([^"]*)"/', $contents, $matches, PREG_SET_ORDER);

        foreach ($matches as [$raw, $directive, $expression]) {
            $name = explode('.', $directive)[0];

            if (in_array($name, NON_ACTION_WIRE_DIRECTIVES, true)) {
                continue;
            }

            // Only the method name matters here; arguments are Blade-interpolated values.
            preg_match('/^\s*([A-Za-z_]\w*)/', $expression, $method);

            if (isset($method[1]) && in_array($method[1], CSP_RESERVED_WORDS, true)) {
                $offenders[] = sprintf(
                    '%s: %s (the CSP evaluator cannot parse `$wire.%s`)',
                    str_replace(base_path().'/', '', $file->getPathname()),
                    trim($raw),
                    $method[1],
                );
            }
        }
    }

    expect($offenders)->toBe([]);
});
