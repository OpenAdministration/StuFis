<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * The two download buttons under the booking history: export/booking/{id}/csv and
 * export/booking/{id}/zip. Both build their file inside a legacy Renderer, which cannot echo it -
 * LegacyController hands whatever a page buffered to the app layout, so the file has to leave as
 * a response (LegacyDownloadException).
 *
 * The plan id comes from the demo seeder, whose first plan carries bookings across several Titel.
 */
uses(DatabaseTransactions::class);

it('downloads the bookings of a budget plan as a zip archive', function (): void {
    $response = $this->actingAs(budgetManager())->get('export/booking/1/zip');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/zip')
        ->assertHeader('Content-Disposition', 'attachment; filename="HHA.zip"');

    $content = $response->getContent();

    // "PK" are the magic bytes of a zip - no HTML wrapper snuck in around the archive
    expect(substr((string) $content, 0, 2))->toBe('PK');

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

it('downloads the booking list of a budget plan as a csv', function (): void {
    $response = $this->actingAs(budgetManager())->get('export/booking/1/csv');

    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=windows-1252');

    expect($response->headers->get('Content-Disposition'))
        ->toStartWith('attachment; filename="')
        ->toEndWith('-Buchungsliste-2025-04-bis-2026-03.csv"');

    $content = mb_convert_encoding((string) $response->getContent(), 'UTF-8', 'WINDOWS-1252');

    expect($content)->toStartWith('Buchungsnummer;Betrag in Euro;')
        // a data row followed, and no layout markup came with it
        ->and(substr_count($content, PHP_EOL))->toBeGreaterThan(0)
        ->and($content)->not->toContain('<html');
});
