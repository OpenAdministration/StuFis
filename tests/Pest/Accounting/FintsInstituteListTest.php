<?php

namespace Tests\Pest\Accounting;

use App\Models\FintsInstitute;
use App\Support\Fints\InstituteListParser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// The bank list is owned entirely by the sync command and the suite runs against a
// persistent database, so roll back rather than leaving a test list behind.
uses(DatabaseTransactions::class);

/**
 * Empties the institute list. konto_credentials.blz references it, so the bank accesses
 * have to go first - inside the test transaction, so both come back afterwards.
 */
function clearInstitutes(): void
{
    DB::table('konto_credentials')->delete();
    FintsInstitute::query()->delete();
}

const LIST_URL = 'https://raw.githubusercontent.com/hbci4j/hbci4java/master/src/main/resources/blz.properties';

/**
 * Two real lines from the upstream list, one with a PIN/TAN endpoint and one without.
 */
function propertiesFixture(array $lines = []): string
{
    return implode("\r\n", $lines === [] ? [
        '50031000=Triodos Bank Deutschland|Frankfurt am Main|TRODDEF1XXX|88|fints2.atruvia.de|https://fints2.atruvia.de/cgi-bin/hbciservlet|300|300|',
        '29000000=Bundesbank|Bremen|MARKDEF1290|09|||||',
    ] : $lines)."\r\n";
}

it('parses the pipe separated columns in hbci4java field order', function (): void {
    $institutes = (new InstituteListParser)->parse(propertiesFixture());

    expect($institutes)->toHaveCount(2)
        ->and($institutes['50031000'])->toBe([
            'name' => 'Triodos Bank Deutschland',
            'location' => 'Frankfurt am Main',
            'bic' => 'TRODDEF1XXX',
            'checksum_method' => '88',
            'rdh_address' => 'fints2.atruvia.de',
            'pin_tan_address' => 'https://fints2.atruvia.de/cgi-bin/hbciservlet',
            'rdh_version' => '300',
            'pin_tan_version' => '300',
        ]);
});

it('turns empty columns into null instead of empty strings', function (): void {
    $institutes = (new InstituteListParser)->parse(propertiesFixture());

    expect($institutes['29000000'])->toMatchArray([
        'name' => 'Bundesbank',
        'rdh_address' => null,
        'pin_tan_address' => null,
        'pin_tan_version' => null,
    ]);
});

it('keeps non numeric protocol version ids such as "plus"', function (): void {
    $institutes = (new InstituteListParser)->parse(propertiesFixture([
        '44351380=Sparkasse UnnaKamen|Unna|WELADED1KAM|00|w019.s-hbci.de|https://hbci-pintan-wf.s-hbci.de/PinTanServlet|220|plus|',
    ]));

    expect($institutes['44351380']['pin_tan_version'])->toBe('plus');
});

it('skips comments, blank lines and anything that is not an 8 digit BLZ', function (): void {
    $parser = new InstituteListParser;
    $institutes = $parser->parse(implode("\n", [
        '# Aktualisierte BLZ-Datei vom 20.05.2026',
        '! also a properties comment',
        '',
        'notablz=Some Bank|Ort|BICBICBICXX|00|||||',
        '1234=Too short|Ort|BICBICBICXX|00|||||',
        '29000000=Bundesbank|Bremen|MARKDEF1290|09|||||',
    ]));

    expect($institutes)->toHaveCount(1)
        ->and($institutes)->toHaveKey('29000000')
        ->and($parser->skipped)->toBe(2);
});

it('tolerates lines that stop early instead of padding every column', function (): void {
    $institutes = (new InstituteListParser)->parse('29000000=Bundesbank|Bremen');

    expect($institutes['29000000'])->toMatchArray([
        'name' => 'Bundesbank',
        'location' => 'Bremen',
        'bic' => null,
        'pin_tan_address' => null,
    ]);
});

it('drops a PIN/TAN endpoint that is not https, keeping the institute itself', function (): void {
    $parser = new InstituteListParser;
    $institutes = $parser->parse(propertiesFixture([
        '50031000=Plain HTTP Bank|Ort|TRODDEF1XXX|88|fints.example.de|http://fints.example.de/servlet|300|300|',
        '29000000=Scheme Missing Bank|Ort|MARKDEF1290|09||fints.example.de/servlet|300|300|',
        '44351380=Proper Bank|Unna|WELADED1KAM|00|w019.s-hbci.de|HTTPS://hbci.example.de/PinTanServlet|220|300|',
    ]));

    expect($institutes['50031000']['pin_tan_address'])->toBeNull()
        ->and($institutes['50031000']['name'])->toBe('Plain HTTP Bank')
        ->and($institutes['29000000']['pin_tan_address'])->toBeNull()
        // Only the scheme has to be https; its casing is the bank's business.
        ->and($institutes['44351380']['pin_tan_address'])->toBe('HTTPS://hbci.example.de/PinTanServlet')
        ->and($parser->insecureEndpoints)->toBe(2);
});

it('reports discarded insecure endpoints and never offers them as PIN/TAN capable', function (): void {
    Http::fake([LIST_URL => Http::response(propertiesFixture([
        '50031000=Plain HTTP Bank|Ort|TRODDEF1XXX|88|fints.example.de|http://fints.example.de/servlet|300|300|',
        '29000000=Bundesbank|Bremen|MARKDEF1290|09|||||',
    ]))]);
    clearInstitutes();

    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])
        ->expectsOutputToContain('1 PIN/TAN-Adressen verworfen')
        ->assertSuccessful();

    expect(FintsInstitute::count())->toBe(2)
        ->and(FintsInstitute::findByBlz('50031000')->pin_tan_address)->toBeNull()
        ->and(FintsInstitute::query()->pinTanCapable()->count())->toBe(0);
});

it('accepts only an https PIN/TAN address as safe to send a PIN to', function (): void {
    expect(FintsInstitute::hasSecurePinTanAddress('https://fints.example.de/servlet'))->toBeTrue()
        ->and(FintsInstitute::hasSecurePinTanAddress('  https://fints.example.de/servlet'))->toBeTrue()
        ->and(FintsInstitute::hasSecurePinTanAddress('HttpS://fints.example.de/servlet'))->toBeTrue()
        ->and(FintsInstitute::hasSecurePinTanAddress('http://fints.example.de/servlet'))->toBeFalse()
        // No scheme at all: phpFinTS would default to something, and we will not guess.
        ->and(FintsInstitute::hasSecurePinTanAddress('fints.example.de/servlet'))->toBeFalse()
        ->and(FintsInstitute::hasSecurePinTanAddress(''))->toBeFalse()
        ->and(FintsInstitute::hasSecurePinTanAddress(null))->toBeFalse();
});

it('lets a later duplicate BLZ win, as loading a properties file would', function (): void {
    $institutes = (new InstituteListParser)->parse(propertiesFixture([
        '29000000=Alter Name|Bremen|MARKDEF1290|09|||||',
        '29000000=Neuer Name|Bremen|MARKDEF1290|09|||||',
    ]));

    expect($institutes)->toHaveCount(1)
        ->and($institutes['29000000']['name'])->toBe('Neuer Name');
});

it('pulls the list into the database', function (): void {
    Http::fake([LIST_URL => Http::response(propertiesFixture())]);
    clearInstitutes();

    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])
        ->expectsOutputToContain('Gelesen: 2 Institute')
        ->assertSuccessful();

    expect(FintsInstitute::count())->toBe(2)
        ->and(FintsInstitute::findByBlz('50031000')->pin_tan_address)
        ->toBe('https://fints2.atruvia.de/cgi-bin/hbciservlet')
        ->and(FintsInstitute::listDate())->not->toBeNull();
});

it('is idempotent and updates only what actually changed', function (): void {
    // A sequence, not two Http::fake() calls: repeated fake() calls append stubs and the
    // first matching one keeps winning, so the second run would re-read the old body.
    Http::fakeSequence()
        ->push(propertiesFixture())
        // Triodos moved its endpoint, the Bundesbank row is untouched.
        ->push(propertiesFixture([
            '50031000=Triodos Bank Deutschland|Frankfurt am Main|TRODDEF1XXX|88|fints2.atruvia.de|https://fints3.atruvia.de/cgi-bin/hbciservlet|300|300|',
            '29000000=Bundesbank|Bremen|MARKDEF1290|09|||||',
        ]));
    clearInstitutes();

    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])
        ->expectsOutputToContain('| neu                     | 2')
        ->assertSuccessful();

    // synced_at has second precision and both runs land in the same second, so backdate
    // to show that the second run stamps unchanged rows too.
    $backdated = Date::parse('2026-01-01 00:00:00');
    FintsInstitute::query()->update(['synced_at' => $backdated]);

    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])
        ->expectsOutputToContain('Geänderte PIN/TAN-Endpunkte')
        ->expectsOutputToContain('| geändert                | 1')
        ->expectsOutputToContain('| unverändert             | 1')
        ->assertSuccessful();

    expect(FintsInstitute::count())->toBe(2)
        ->and(FintsInstitute::findByBlz('50031000')->pin_tan_address)
        ->toBe('https://fints3.atruvia.de/cgi-bin/hbciservlet')
        // The untouched Bundesbank row still counts as seen in this run.
        ->and(FintsInstitute::findByBlz('29000000')->synced_at->greaterThan($backdated))->toBeTrue()
        ->and(FintsInstitute::listDate()->greaterThan($backdated))->toBeTrue();
});

it('refuses a suspiciously short list rather than emptying the table', function (): void {
    Http::fake([LIST_URL => Http::response(propertiesFixture())]);
    clearInstitutes();

    // Default --min-entries is 1000, the fixture has 2.
    $this->artisan('stufis:fints-institutes-update')
        ->expectsOutputToContain('Quelle sieht unvollständig aus')
        ->assertFailed();

    expect(FintsInstitute::count())->toBe(0);
});

it('writes nothing on a dry run', function (): void {
    Http::fake([LIST_URL => Http::response(propertiesFixture())]);
    clearInstitutes();

    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1, '--dry-run' => true])
        ->expectsOutputToContain('nichts geschrieben')
        ->assertSuccessful();

    expect(FintsInstitute::count())->toBe(0);
});

it('keeps institutes that vanished upstream unless asked to prune', function (): void {
    $shrunk = propertiesFixture(['29000000=Bundesbank|Bremen|MARKDEF1290|09|||||']);
    Http::fakeSequence()
        ->push(propertiesFixture())
        ->push($shrunk)
        ->push($shrunk);
    clearInstitutes();
    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])->assertSuccessful();

    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])
        ->expectsOutputToContain('bleiben aber erhalten')
        ->assertSuccessful();
    expect(FintsInstitute::count())->toBe(2);

    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1, '--prune' => true])
        ->expectsOutputToContain('veraltete Institute gelöscht')
        ->assertSuccessful();
    expect(FintsInstitute::count())->toBe(1)
        ->and(FintsInstitute::findByBlz('50031000'))->toBeNull();
});

it('never prunes an institute that a bank access still points at', function (): void {
    $shrunk = propertiesFixture(['29000000=Bundesbank|Bremen|MARKDEF1290|09|||||']);
    Http::fakeSequence()->push(propertiesFixture())->push($shrunk);
    clearInstitutes();
    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])->assertSuccessful();

    // Somebody banks with Triodos, and Triodos then drops out of the list.
    DB::table('konto_credentials')->insert([
        'name' => 'Test', 'blz' => '50031000', 'owner_id' => user()->id, 'bank_username' => 'test',
    ]);

    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1, '--prune' => true])
        ->expectsOutputToContain('Nicht gelöscht, weil Bankzugänge darauf verweisen: 50031000')
        ->assertSuccessful();

    // Kept, so neither the foreign key nor the bank access breaks.
    expect(FintsInstitute::count())->toBe(2)
        ->and(FintsInstitute::findByBlz('50031000'))->not->toBeNull();
});

it('reads a local file instead of downloading', function (): void {
    Http::fake();
    clearInstitutes();

    $path = tempnam(sys_get_temp_dir(), 'blz').'.properties';
    file_put_contents($path, propertiesFixture());

    $this->artisan('stufis:fints-institutes-update', ['--file' => $path, '--min-entries' => 1])
        ->assertSuccessful();

    unlink($path);
    Http::assertNothingSent();
    expect(FintsInstitute::count())->toBe(2);
});

it('fails loudly when the source cannot be fetched', function (): void {
    Http::fake([LIST_URL => Http::response('not found', 404)]);
    clearInstitutes();

    $this->artisan('stufis:fints-institutes-update')
        ->expectsOutputToContain('HTTP 404')
        ->assertFailed();

    expect(FintsInstitute::count())->toBe(0);
});

it('fails when a local file is missing', function (): void {
    $this->artisan('stufis:fints-institutes-update', ['--file' => '/nope/blz.properties'])
        ->expectsOutputToContain('Datei nicht lesbar')
        ->assertFailed();
});

it('resolves a German IBAN to its institute and ignores foreign ones', function (): void {
    Http::fake([LIST_URL => Http::response(propertiesFixture())]);
    clearInstitutes();
    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])->assertSuccessful();

    expect(FintsInstitute::findByIban('DE89 5003 1000 0123 4567 89')?->name)
        ->toBe('Triodos Bank Deutschland')
        ->and(FintsInstitute::findByIban('de89500310000123456789')?->blz)->toBe('50031000')
        ->and(FintsInstitute::findByIban('AT611904300234573201'))->toBeNull()
        ->and(FintsInstitute::findByIban('DE8950031000'))->toBeNull();
});

it('accepts an integer BLZ, as the legacy code hands it over', function (): void {
    Http::fake([LIST_URL => Http::response(propertiesFixture())]);
    clearInstitutes();
    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])->assertSuccessful();

    expect(FintsInstitute::findByBlz(50031000)?->name)->toBe('Triodos Bank Deutschland');
});

it('finds institutes by name, BLZ or BIC and only lists PIN/TAN capable ones on demand', function (): void {
    Http::fake([LIST_URL => Http::response(propertiesFixture())]);
    clearInstitutes();
    $this->artisan('stufis:fints-institutes-update', ['--min-entries' => 1])->assertSuccessful();

    expect(FintsInstitute::query()->search('Triodos')->pluck('blz')->all())->toBe(['50031000'])
        ->and(FintsInstitute::query()->search('2900')->pluck('blz')->all())->toBe(['29000000'])
        ->and(FintsInstitute::query()->search('TRODDEF1')->pluck('blz')->all())->toBe(['50031000'])
        ->and(FintsInstitute::query()->search('')->count())->toBe(2)
        ->and(FintsInstitute::query()->pinTanCapable()->pluck('blz')->all())->toBe(['50031000']);
});
