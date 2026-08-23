<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * The DATEV Export button next to the .csv/.zip downloads under the booking history: rendered
 * only while the "datev" setting is on. Unlike the budget plan page, which carries the same
 * button disabled for non-finance readers, these routes already require ref-finanzen - the same
 * group the DATEV download is gated on - so there is no disabled state to cover here.
 */
uses(DatabaseTransactions::class);

function bookingHistory($user): string
{
    return legacyHtml(test()->actingAs($user)->get(route('legacy.booking.history', ['hhp_id' => 1])));
}

it('offers the DATEV export when the setting is on', function (): void {
    Setting::set('datev', true);

    $html = bookingHistory(budgetManager());

    expect($html)->toContain('DATEV Export')
        ->and($html)->toContain(route('datev.export', ['hhpId' => 1]));
});

it('hides the DATEV export while the setting is off', function (): void {
    Setting::set('datev', false);

    expect(bookingHistory(budgetManager()))->not->toContain('DATEV Export');
});
