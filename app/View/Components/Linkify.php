<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Renders its slot, auto-linking recognised tokens: project references like
 * IP-<jahr>-<projekt_id>-A<auslagen_id> and plain http(s) URLs. The slot may contain
 * HTML; only the matched tokens are turned into links, the rest is passed through
 * untouched.
 */
class Linkify extends Component
{
    /**
     * Split the given (already-escaped) HTML into plain-text and link segments.
     *
     * @return list<array{type: 'text'|'link', html: string, href?: string}>
     */
    public function segments(string $html): array
    {
        $matches = $this->collectMatches($html);
        // Earliest match first; on a tie the longer match wins so an outer token isn't
        // pre-empted by one nested inside it.
        usort($matches, static fn ($a, $b) => [$a['start'], $b['length']] <=> [$b['start'], $a['length']]);

        $segments = [];
        $offset = 0;
        foreach ($matches as $match) {
            if ($match['start'] < $offset) {
                continue; // overlaps a link we already emitted
            }
            if ($match['start'] > $offset) {
                $segments[] = ['type' => 'text', 'html' => substr($html, $offset, $match['start'] - $offset)];
            }
            $segments[] = ['type' => 'link', 'html' => $match['html'], 'href' => $match['href']];
            $offset = $match['start'] + $match['length'];
        }
        if ($offset < strlen($html)) {
            $segments[] = ['type' => 'text', 'html' => substr($html, $offset)];
        }

        return $segments;
    }

    /**
     * @return list<array{start: int, length: int, html: string, href: string}>
     */
    private function collectMatches(string $html): array
    {
        $matches = [];

        // Project references → link to the expense.
        preg_match_all('/IP-\d{2,4}-(\d+)-A(\d+)/', $html, $found, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($found as $m) {
            $matches[] = [
                'start' => $m[0][1],
                'length' => strlen($m[0][0]),
                'html' => $m[0][0],
                'href' => route('legacy.expense-long', ['projekt_id' => $m[1][0], 'auslagen_id' => $m[2][0]]),
            ];
        }

        // Plain http(s) URLs. Trailing sentence punctuation is excluded from the link.
        preg_match_all('#https?://[^\s<]+#', $html, $found, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($found as $m) {
            $url = rtrim($m[0][0], '.,;:!?');
            $matches[] = [
                'start' => $m[0][1],
                'length' => strlen($url),
                'html' => $url,
                // The slot is HTML-escaped; decode entities (e.g. &amp;) for the real href.
                'href' => html_entity_decode($url, ENT_QUOTES | ENT_HTML5),
            ];
        }

        return $matches;
    }

    public function render(): View
    {
        return view('components.linkify');
    }
}
