<?php

namespace App\Support\Fints;

/**
 * Parses the `blz.properties` bank list shipped by hbci4java.
 *
 * We deliberately do not consume Die Deutsche Kreditwirtschaft's own FinTS-Bankenliste
 * here: it is handed out to registered FinTS product vendors only, and redistributing it
 * as part of a software product is expressly forbidden. hbci4java maintains an equivalent
 * list publicly (LGPL 2.1) and refreshes it roughly monthly, which is what we pull.
 *
 * One line per institute, the Bankleitzahl as the properties key:
 *
 *   72120207=UniCredit Bank - HypoVereinsbank|Aschheim|HYVEDEM1093|99|hbci.hypo.de|https://…|300|300|
 *   BLZ     =name                            |location|bic        |cs|rdh_address |pin_tan  |rdh|pin_tan version
 *
 * Field order mirrors org.kapott.hbci.manager.BankInfo::parse().
 */
class InstituteListParser
{
    /**
     * Columns of the pipe-separated value, in file order.
     */
    private const array COLUMNS = [
        'name',
        'location',
        'bic',
        'checksum_method',
        'rdh_address',
        'pin_tan_address',
        'rdh_version',
        'pin_tan_version',
    ];

    /**
     * How many lines were well-formed but unusable (no BLZ key, no name).
     */
    public int $skipped = 0;

    /**
     * @return array<string, array<string, string|null>> keyed by BLZ
     */
    public function parse(string $contents): array
    {
        $this->skipped = 0;
        $institutes = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);

            // Properties comments. Blank lines fall out here too.
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '!')) {
                continue;
            }

            [$blz, $fields] = array_pad(explode('=', $line, 2), 2, null);
            $blz = trim((string) $blz);

            if (! preg_match('/^\d{8}$/', $blz)) {
                $this->skipped++;

                continue;
            }

            $values = array_pad(explode('|', (string) $fields), count(self::COLUMNS), null);
            $institute = [];
            foreach (self::COLUMNS as $index => $column) {
                $value = trim((string) $values[$index]);
                $institute[$column] = $value === '' ? null : $value;
            }

            // A row without a name carries nothing we could show a user.
            if ($institute['name'] === null) {
                $this->skipped++;

                continue;
            }

            // Later entries win, as they would when Java loads the properties file.
            $institutes[$blz] = $institute;
        }

        return $institutes;
    }
}
