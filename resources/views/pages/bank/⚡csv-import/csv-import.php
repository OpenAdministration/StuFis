<?php

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\User;
use App\Rules\CsvTransactionImport\BalanceColumnRule;
use App\Rules\CsvTransactionImport\DateColumnRule;
use App\Rules\CsvTransactionImport\IbanColumnRule;
use App\Rules\CsvTransactionImport\MoneyColumnRule;
use Carbon\Exceptions\InvalidFormatException;
use forms\projekte\auslagen\AuslagenHandler2;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\Regex\Regex;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    use WithFileUploads;

    /**
     * @var TemporaryUploadedFile the content of the csv file
     */
    public $csv;

    /**
     * @var string which separator to use to parse the csv file
     */
    public $separator;

    /**
     * @var int the id of the bank account to import into
     */
    #[Url]
    public $account_id = 1;

    /**
     * @var Collection the mapping between the int number csv columns and the database column
     */
    public $mapping;

    public $db_col_types;

    /**
     * @var Collection<Collection<string>> normalized and ordered csv data
     */
    public $data;

    public $header;

    /**
     * @var bool CSV entries in order, or not
     */
    public $csvOrderReversed = false;

    public function mount()
    {
        $this->authorize('finance', User::class);
        $this->data = collect();

        $this->updatedAccountId();

        // researches for each db_column name (keys of the mapping) the db_col_type
        foreach ($this->mapping as $db_column => $csv_colum) {
            $this->db_col_types[$db_column] = DB::getSchemaBuilder()->getColumnType((new BankTransaction)->getTable(), $db_column);
        }
    }

    public function rules(): array
    {
        $latestTransaction = BankTransaction::where('konto_id', $this->account_id)->orderBy('id', 'desc')->first();

        $saldoMapped = filled($this->mapping->get('saldo'));
        $valueMapped = filled($this->mapping->get('value'));

        return [
            'csv' => 'required|file|extensions:csv|mimes:csv,txt',
            'mapping.date' => [
                'bail', 'required', 'int',
                ...$this->whenMapped('date', fn ($col) => new DateColumnRule($col)),
            ],
            'mapping.valuta' => [
                'bail', 'required', 'int',
                ...$this->whenMapped('valuta', fn ($col) => new DateColumnRule($col)),
            ],
            'mapping.type' => 'required|int',
            'mapping.value' => [
                'bail', 'required', 'int',
                ...$this->whenMapped('value', fn ($col) => new MoneyColumnRule($col)),
            ],
            'mapping.saldo' => [
                'int',
                ...$this->whenMapped('saldo', fn ($col) => new MoneyColumnRule($col)),
                ...($saldoMapped && $valueMapped ? [new BalanceColumnRule(
                    $this->data->pluck($this->mapping->get('value')),
                    $this->data->pluck($this->mapping->get('saldo')),
                    $latestTransaction?->saldo
                )] : []),
            ],
            'mapping.empf_name' => 'required|int',
            'mapping.empf_bic' => 'sometimes|int',
            'mapping.empf_iban' => [
                'bail', 'required', 'int',
                ...$this->whenMapped('empf_iban', fn ($col) => new IbanColumnRule($col)),
            ],
            'mapping.zweck' => 'required|int',
        ];
    }

    /**
     * Only instantiate a column rule when the field is actually mapped to a CSV column.
     * Prevents constructing rules with pluck('') garbage when the user hasn't selected a column yet.
     *
     * @return array<ValidationRule>
     */
    private function whenMapped(string $field, Closure $make): array
    {
        $index = $this->mapping->get($field);

        return filled($index) ? [$make($this->data->pluck($index))] : [];
    }

    private function parseCSV(): void
    {
        try {
            // Read the upload straight from Livewire's temporary file. We must NOT call
            // $this->csv->store() first: when the default disk equals the Livewire
            // temp-upload disk (both "local" here), store() *moves* the temp file away,
            // so the very next read would fail with "No such file or directory" and get
            // swallowed into konto.csv-parse-error. Nothing reads the stored copy back
            // anyway, so persisting it only littered storage/app with orphaned uploads.
            $content = utf8Content($this->csv);
            // explode content in lines
            $content = str($content);
            $lines = $content->explode(PHP_EOL);

            // guess csv separator
            $amountComma = $content->substrCount(',');
            $amountSemicolon = $content->substrCount(';');
            $this->separator = $amountSemicolon > $amountComma ? ';' : ',';

            // extract header and data, explode data with csv separator guesses above
            $this->header = str_getcsv((string) $lines->first(), $this->separator, escape: '\\');
            $this->data = $lines->except(0)
                // reject fully empty lines and lines with only separators inside
                ->reject(fn ($line): bool => empty($line) || Regex::match('/^(,*|;*)\r?\n?$/', $line)->hasMatch())
                // transform csv lines to array
                ->map(fn ($line) => str_getcsv((string) $line, $this->separator, escape: ''))
                ->map(function ($lineArray) {
                    // normalize data
                    foreach ($lineArray as $key => $cell) {
                        // tests
                        $moneyTest = Regex::match('/^(\-?)(\d+)([,\.](\d{1,2}))?$/', $cell);
                        $dateTest = Regex::match('/^([0-3]?\d)\.([01]?\d)\.((20)?\d{2})$/', $cell);
                        // conversions
                        if ($moneyTest->hasMatch()) {
                            // normalize money
                            $g = $moneyTest->groups();
                            $lineArray[$key] = $g[1] // sign
                                .Str::padRight($g[2] ?? '', 1, '0') //  money before delimiter (at least 1 digit)
                                .'.' // delimiter (3rd group, with the rest together)
                                .Str::padRight($g[4] ?? '', 2, '0'); // cents after delimiter (at least 2 digits)
                        } elseif ($dateTest->hasMatch()) {
                            // normalize dates
                            $g = $dateTest->groups();
                            $lineArray[$key] = Str::padLeft($g[3], 4, '20') // year
                                .'-'.Str::padLeft($g[2], 2, '0')
                                .'-'.Str::padLeft($g[1], 2, '0');
                        }
                    }

                    return $lineArray;
                });

            if ($this->csvOrderReversed) {
                $this->data = $this->data->reverse();
            }
        } catch (Throwable) {
            $this->data = collect();
            $this->header = [];
            $this->addError('csv', __('konto.csv-parse-error'));
        }
    }

    public function updatedCsv(): void
    {
        $this->validateOnly('csv');
        if (in_array($this->csv->getMimeType(), ['text/csv', 'text/plain'])) {
            $this->parseCSV();
            // Run validation against mapping presets only after a successful parse.
            // Keeping this outside parseCSV()'s try-catch ensures ValidationException
            // bubbles to Livewire instead of being swallowed as a parse error.
            if ($this->data->isNotEmpty()) {
                $hasPreset = $this->mapping->reject(fn ($value) => $value === '')->count() > 0;
                if ($hasPreset) {
                    $this->validate();
                }
            }
        }
    }

    public function save()
    {
        $this->validate();
        // mapping als vorlage speichern
        $account = BankAccount::findOrFail($this->account_id);
        $account->csv_import_settings = [
            'csv_import_mapping' => $this->mapping,
            'csv_order_reversed' => $this->csvOrderReversed,
        ];
        $account->save();
        $last_id = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first('id')->id ?? 1;

        // In case no saldo is given in CSV, we need to calculate it row by row ourselves
        // first Saldo should be sourced from last entry in DB, if there is no entry, we assume 0
        // we decided to set saldo to 0 if it is the first entry in the DB - alternative would have been to throw an error
        $currentBalance = $account->bankTransactions()->orderBy('id', 'desc')->first()?->saldo ?? 0;

        // References whose Auslage could not be auto-marked "paid". hookZahlung() is a
        // best-effort side effect (it reaches into legacy code that can fail for unrelated
        // reasons); it must never roll back the actual import, which is the source of truth.
        // We pass flash:false so it reports failures via its return value instead of the
        // legacy session flash, and surface them ourselves as one warning message.
        $failedHooks = [];

        // create BankTransaction with values from $data, according to the keys assigned in $mapping
        try {
            DB::beginTransaction();
            foreach ($this->data as $row) {
                $transaction = new BankTransaction;
                $transaction->id = ++$last_id;
                $transaction->konto_id = $this->account_id;

                foreach ($this->mapping as $db_col_name => $csv_col_id) {
                    // filled() (not ! empty()) so CSV column index 0 counts as mapped —
                    // ! empty(0) is false, which silently dropped any field mapped to the
                    // first column (e.g. a NOT NULL "date", failing the whole import).
                    if (filled($csv_col_id)) {
                        $transaction->$db_col_name = $this->formatDataDb($row[$csv_col_id], $db_col_name);
                    } elseif ($db_col_name === 'saldo') {
                        $currentValue = str($row[$this->mapping['value']])->replace(',', '.');
                        $currentBalance = bcadd((string) $currentBalance, (string) $currentValue, 2);
                        $transaction->$db_col_name = $this->formatDataDb($currentBalance, $db_col_name);
                    }
                }
                try {
                    if (($failedRef = AuslagenHandler2::hookZahlung($transaction->zweck, flash: false)) !== null) {
                        $failedHooks[] = $failedRef;
                    }
                } catch (Throwable $e) {
                    report($e);
                    $failedHooks[] = $transaction->zweck;
                }
                $transaction->save();
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            // Log the swallowed cause: the user only sees a generic message, so without
            // this the import would fail silently with no trace for ops to diagnose.
            report($e);
            $this->addError('csv', __('konto.csv-import-error'));

            return;
        }

        $newBalance = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first()->saldo;

        /*
         * Flux toast is gone after redirect. That's not a nice thing to do...
        Flux::toast(
            text: __('konto.csv-import-success-msg', ['new-saldo' => $newBalance, 'transaction-amount' => $this->data->count()]),
            heading: __('Success'),
            duration: 0,
            variant: 'success',

        );
        */

        // Forward to the imported account's konto page. The legacy route selects the
        // account via path segments konto/{hhp_id}/{konto_id}. Passing ['konto' => id]
        // instead produced /konto?konto=id, landing on the generic overview.
        $hhp = LegacyBudgetPlan::latest()?->id;

        $message = $failedHooks === []
            ? [
                'text' => __('konto.csv-import-success-msg', ['new-saldo' => $newBalance, 'transaction-amount' => $this->data->count()]),
                'type' => 'success',
            ]
            : [
                'text' => __('konto.csv-import-hook-warning-msg', [
                    'new-saldo' => $newBalance,
                    'transaction-amount' => $this->data->count(),
                    'references' => implode(', ', $failedHooks),
                ]),
                'type' => 'warning',
            ];

        return to_route('legacy.konto', ['hhp_id' => $hhp, 'konto_id' => $this->account_id])
            ->with(['message' => $message]);
    }

    public function with(): array
    {
        $accounts = BankAccount::all();
        $labels = (new BankTransaction)->getLabels();

        $latestTransaction = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->first();

        return [
            'accounts' => $accounts,
            'firstNewTransaction' => $this->data->first(),
            'lastNewTransaction' => $this->data->last(),
            'latestTransaction' => $latestTransaction,
            'labels' => $labels,
        ];
    }

    /**
     * Generates a mapping array with
     *
     * @var array<string> The given csv row numbers to prefill as string
     *
     * @return Collection<string> keys: the db column names / fillables of BankTransaction,
     *                            values: empty string or the prefills from
     */
    private function createMapping(array $prefill = []): Collection
    {
        $foo = new BankTransaction;
        $emptyMapping = collect(array_flip($foo->getFillable()));

        return $emptyMapping->map(fn ($value, $key) => $prefill[$key] ?? '');
    }

    /**
     * @return void gets called if Account Id changes
     */
    public function updatedAccountId(): void
    {
        $account = BankAccount::findOrFail($this->account_id);
        $this->mapping = $this->createMapping($account->csv_import_settings['csv_import_mapping'] ?? []);
        $old_Order = $this->csvOrderReversed;
        $new_Order = (bool) ($account->csv_import_settings['csv_order_reversed'] ?? false);
        if ($old_Order !== $new_Order) {
            $this->data = $this->data->reverse();
        }
        $this->csvOrderReversed = $new_Order;
        $this->resetValidation();
    }

    /**
     * is called when mapping got updated
     */
    public function updatedMapping(mixed $value, string $key): void
    {
        $this->validateOnly("mapping.$key");

        if (in_array($key, ['value', 'saldo']) && filled($this->mapping->get('saldo'))) {
            $this->validateOnly('mapping.saldo');
        }
    }

    public function formatDataDb(string|int $value, string $db_col_name): int|string
    {
        $type = $this->db_col_types[$db_col_name];

        // if($type === 'decimal') dd([$value, (float) $value,$db_col_name]);
        return match ($type) {
            'integer' => (int) $value,
            'date' => guessDate($value, 'Y-m-d'),
            'decimal' => $value, // no casting needed, string is expected
            default => $value,
        };
    }

    public function formatDataView(string|int $value, string $db_col_name): int|string
    {
        $type = $this->db_col_types[$db_col_name];
        if ($db_col_name === 'empf_iban') {
            $type = 'iban';
        }
        if ($db_col_name === 'empf_bic') {
            $type = 'bic';
        }

        return match ($type) {
            'date' => $this->previewDate($value),
            'decimal' => number_format((float) $value, 2, ',', '.').' €',
            'iban' => iban_to_human_format($value),
            default => $value
        };
    }

    /**
     * Format a value as a date for the preview, falling back to the raw value when
     * the mapped column does not actually contain a date (e.g. the date field is
     * mapped to an IBAN column). The preview must never hard-fail: showing the raw
     * value lets the user spot the misaligned mapping instead of hitting a 500.
     */
    private function previewDate(string|int $value): string|int
    {
        try {
            return guessDate((string) $value, 'd.m.Y');
        } catch (InvalidFormatException|TypeError) {
            return $value;
        }
    }

    /**
     * Clear the uploaded CSV and all derived state so the user can re-upload.
     */
    public function clearCsv(): void
    {
        $this->reset(['csv', 'header', 'separator', 'csvOrderReversed']);
        $this->data = collect();
        $this->resetValidation('csv');
    }

    /**
     * Change the order of CSV entries in current upload
     */
    public function reverseCsvOrder(): void
    {
        $this->data = $this->data->reverse();
        $this->csvOrderReversed = ! $this->csvOrderReversed;
        $this->resetValidation();
        $this->validate();
    }
};
