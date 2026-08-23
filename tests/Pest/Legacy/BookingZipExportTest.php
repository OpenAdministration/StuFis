<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * The "als .zip" download on the booking history page (export/booking/{hhp-id}/zip). It hands
 * one CSV per Haushaltstitel back inside an archive, and it has to leave the legacy renderer
 * as a response - echoing it would land the binary inside the app layout.
 */
uses(DatabaseTransactions::class);

it('downloads the bookings of a budget plan as a zip archive', function (): void {
    // the seeded demo plan carries bookings across several Titel
    $response = $this->actingAs(budgetManager())->get('export/booking/1/zip');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/zip')
        ->assertHeader('Content-Disposition', 'attachment; filename="HHA.zip"');

    $content = $response->getContent();

    expect($content)->not->toBeEmpty()
        // no HTML wrapper snuck in around the archive
        ->and(substr((string) $content, 0, 2))->toBe('PK');

    $path = tempnam(sys_get_temp_dir(), 'hha-test');
    file_put_contents($path, $content);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();
    unlink($path);

    expect($names)->not->toBeEmpty()
        ->and($names)->each->toEndWith('.csv');
});
