@php use Cknow\Money\Money; @endphp
@props([
    'planned',   // Money — the Soll / budgeted ceiling
    'booked',    // Money — Gebucht (actually booked)
    'committed', // Money — Beschlossen (booked ⊆ committed, guaranteed while every booking has a parent project)
])

@php
    /** @var Money $planned */
    /** @var Money $booked */
    /** @var Money $committed */

    // Work in integer minor units (cents) so the segment maths is exact and never drifts from
    // the formatted figures. Booked ⊆ committed holds by construction (see prop note), but the
    // bar still tolerates booked > planned (real money spent over budget) as its own segment.
    $p = (int) $planned->getAmount();
    $b = (int) $booked->getAmount();
    $c = (int) $committed->getAmount();

    // The track scales to the larger of Plan or Beschlossen, so an overspend (up to 400 %+) stays
    // on-screen with the 100 % Soll line drawn proportionally inside it (matches the PM draft).
    $trackMax = max($p, $c, 1); // guard: an unbudgeted (Plan = 0) titel would divide by zero

    $pct = static fn (int $cents): float => max(0, $cents) / $trackMax * 100;

    // Five ordered segments, split at the Soll line (Plan). Widths sum to $trackMax.
    // Colours: indigo = Gebucht (real, settled money), amber = Beschlossen (reserved, pending),
    // red = over the ceiling — darker red for money already spent past it, lighter for committed.
    $segments = [
        // Gebucht within budget
        ['w' => $pct(min($b, $p)),              'class' => 'bg-indigo-500 dark:bg-indigo-400', 'key' => 'booked'],
        // Gebucht beyond budget — real money already spent over the ceiling (most severe)
        ['w' => $pct(max(0, $b - $p)),          'class' => 'bg-red-600 dark:bg-red-500',       'key' => 'booked-over'],
        // zusätzlich Beschlossen, still within budget
        ['w' => $pct(max(0, min($c, $p) - $b)), 'class' => 'bg-amber-400 dark:bg-amber-500',   'key' => 'committed'],
        // zusätzlich Beschlossen, beyond the ceiling (Überzogen)
        ['w' => $pct(max(0, $c - max($b, $p))), 'class' => 'bg-red-400 dark:bg-red-400/70',    'key' => 'committed-over'],
    ];

    $available = max(0, $p - $c);              // remainder shown while under budget
    $overrun = max(0, $c - $p);                // amount past the Soll shown once overspent
    $sollPct = $p / $trackMax * 100;           // position of the 100 % (Soll) marker
    $ratioPct = $p > 0 ? (int) round($c / $p * 100) : null; // committed as % of Soll, for the badge
    $overspent = $c > $p;
    // keep the marker caption inside the card: sit it left of the line once the line is past halfway
    $labelLeft = $sollPct > 50;
@endphp

<flux:card class="space-y-5">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('budget-plan.item.meter.heading') }}</flux:heading>
        @if($ratioPct !== null)
            <flux:badge :color="$overspent ? 'red' : 'zinc'" size="sm">
                {{ $ratioPct }} % {{ __('budget-plan.item.meter.of-soll') }}
            </flux:badge>
        @endif
    </div>

    {{-- the bar --}}
    <div class="relative">
        <div class="flex h-7 w-full overflow-hidden rounded-lg bg-zinc-100 ring-1 ring-black/5 dark:bg-zinc-800 dark:ring-white/10">
            @foreach($segments as $segment)
                @if($segment['w'] > 0)
                    <div class="{{ $segment['class'] }} h-full" style="width: {{ $segment['w'] }}%"></div>
                @endif
            @endforeach
        </div>

        {{-- 100 % (Soll) marker — only when the line falls inside the bar, i.e. the titel is
             overspent. When it would sit flush at the right edge (nothing overspent) it is
             redundant with the bar's own end, so it is omitted. --}}
        @if($p > 0 && $overspent)
            <div class="absolute inset-y-0 w-px bg-zinc-700 dark:bg-zinc-200" style="left: {{ $sollPct }}%">
                {{-- caption sits below the bar so it never collides with the badge in the top-right --}}
                <span @class([
                    'absolute top-full mt-1 text-xs font-medium whitespace-nowrap text-zinc-500 dark:text-zinc-400',
                    'end-0 text-right' => $labelLeft,
                    'start-0' => ! $labelLeft,
                ])>{{ __('budget-plan.item.meter.soll') }}</span>
            </div>
        @endif
    </div>

    {{-- legend / figures --}}
    <div class="flex flex-wrap items-center gap-x-8 gap-y-3 border-t border-zinc-100 pt-4 text-sm dark:border-zinc-700/60">
        <span class="flex items-center gap-2">
            <span class="size-2.5 rounded-full bg-indigo-500 dark:bg-indigo-400"></span>
            <span class="text-zinc-500 dark:text-zinc-400">{{ __('budget-plan.view.col.booked') }}</span>
            <span class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">{{ $booked->format() }}</span>
        </span>
        <span class="flex items-center gap-2">
            <span class="size-2.5 rounded-full bg-amber-400 dark:bg-amber-500"></span>
            <span class="text-zinc-500 dark:text-zinc-400">{{ __('budget-plan.view.col.committed') }}</span>
            <span class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">{{ $committed->format() }}</span>
        </span>
        {{-- while under budget this shows what's left (Verfügbar); once over, it flips to the
             amount past the Soll (Überzogen) — showing "Verfügbar 0" when overspent would mislead --}}
        <span class="flex items-center gap-2">
            <span @class([
                'size-2.5 rounded-full',
                'bg-red-400 dark:bg-red-400/70' => $overspent,
                'border border-zinc-300 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800' => ! $overspent,
            ])></span>
            <span class="text-zinc-500 dark:text-zinc-400">{{ $overspent ? __('budget-plan.item.meter.overspent') : __('budget-plan.item.meter.available') }}</span>
            <span @class([
                'font-medium tabular-nums',
                'text-red-600 dark:text-red-400' => $overspent,
                'text-zinc-900 dark:text-zinc-100' => ! $overspent,
            ])>{{ ($overspent ? Money::EUR($overrun) : Money::EUR($available))->format() }}</span>
        </span>
        <span class="flex items-center gap-2 ms-auto">
            <span class="text-zinc-500 dark:text-zinc-400">{{ __('budget-plan.view.col.planned') }}</span>
            <span class="font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $planned->format() }}</span>
        </span>
    </div>
</flux:card>
