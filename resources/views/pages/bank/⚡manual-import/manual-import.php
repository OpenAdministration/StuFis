<?php

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\User;
use App\Rules\CamtImport\AccountIbanRule;
use App\Rules\CamtImport\BalanceConsistencyRule;
use App\Rules\CamtImport\ContinuityRule;
use App\Rules\CsvTransactionImport\BalanceColumnRule;
use App\Rules\CsvTransactionImport\DateColumnRule;
use App\Rules\CsvTransactionImport\IbanColumnRule;
use App\Rules\CsvTransactionImport\MoneyColumnRule;
use App\Support\Import\CamtImportParser;
use App\Support\Import\CsvImportParser;
use Carbon\Exceptions\InvalidFormatException;
use forms\projekte\auslagen\AuslagenHandler2;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    use WithFileUploads;

    /**
     * @var TemporaryUploadedFile the uploaded statement file (CSV or CAMT XML)
     */
    public $upload;

    /**
     * @var string detected upload format: 'csv' or 'camt'
     */
    public string $format = 'csv';

    /**
     * @var string|null IBAN the CAMT statement belongs to, for the account sanity check
     */
    public $statementIban = null;

    /**
     * @var string|null opening balance reported by a CAMT statement, for the end-to-end check
     */
    public $openingBalance = null;

    /**
     * @var string|null closing balance reported by a CAMT statement, for the end-to-end check
     */
    public $closingBalance = null;

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

        // CAMT is self-describing: there is no user column mapping to validate. The data is
        // already normalized by the parser, so we validate the produced rows directly and add
        // a statement-balance cross-check unique to CAMT (the file carries the closing balance,
        // but no per-entry running saldo — that is still computed in save()).
        if ($this->format === 'camt') {
            return [
                'upload' => [
                    'required', 'file', 'extensions:xml', 'mimes:xml,application/xml,text/xml',
                    new AccountIbanRule(BankAccount::find($this->account_id)?->iban, $this->statementIban),
                    new DateColumnRule($this->data->pluck('date')),
                    new MoneyColumnRule($this->data->pluck('value')),
                    new IbanColumnRule($this->data->pluck('empf_iban')),
                    new BalanceConsistencyRule($this->data->pluck('value'), $this->openingBalance, $this->closingBalance),
                    new ContinuityRule($latestTransaction?->saldo, $this->openingBalance),
                ],
            ];
        }

        $saldoMapped = filled($this->mapping->get('saldo'));
        $valueMapped = filled($this->mapping->get('value'));

        return [
            'upload' => 'required|file|extensions:csv|mimes:csv,txt',
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
            // $this->upload->store() first: when the default disk equals the Livewire
            // temp-upload disk (both "local" here), store() *moves* the temp file away,
            // so the very next read would fail with "No such file or directory" and get
            // swallowed into konto.csv-parse-error. Nothing reads the stored copy back
            // anyway, so persisting it only littered storage/app with orphaned uploads.
            $result = (new CsvImportParser)->parse(utf8Content($this->upload));
            $this->separator = $result['separator'];
            $this->header = $result['header'];
            $this->data = $result['data'];

            if ($this->csvOrderReversed) {
                $this->data = $this->data->reverse();
            }
        } catch (Throwable) {
            $this->data = collect();
            $this->header = [];
            $this->addError('upload', __('konto.csv-parse-error'));
        }
    }

    private function parseCamt(): void
    {
        try {
            $result = (new CamtImportParser)->parse($this->upload->getRealPath());
            $this->data = $result['rows'];
            $this->statementIban = $result['accountIban'];
            $this->openingBalance = $result['openingBalance'];
            $this->closingBalance = $result['closingBalance'];
            // CAMT needs no column-mapping UI: the rows are keyed by DB column already.
            $this->header = [];
            $this->mapping = $this->camtMapping();
        } catch (Throwable) {
            $this->data = collect();
            $this->header = [];
            $this->statementIban = null;
            $this->openingBalance = null;
            $this->closingBalance = null;
            $this->addError('upload', __('konto.camt-parse-error'));
        }
    }

    public function updatedUpload(): void
    {
        // Detect the format from the file content so the file rule (csv vs xml) and the parse
        // branch agree.
        $this->format = (new CamtImportParser)->isCamt($this->upload->getRealPath()) ? 'camt' : 'csv';

        if ($this->format === 'camt') {
            // Parse first: the CAMT 'upload' rule validates the produced rows (dates, balance),
            // so validation must run against the parsed data, not the still-empty default.
            $this->parseCamt();
            if ($this->data->isNotEmpty()) {
                $this->validateOnly('upload');
            }

            return;
        }

        $this->validateOnly('upload');
        if (in_array($this->upload->getMimeType(), ['text/csv', 'text/plain'])) {
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
        $account = BankAccount::findOrFail($this->account_id);
        // Persist the column mapping as a template — CSV only. The CAMT mapping is a fixed
        // identity mapping, not something the user tuned, so it must not overwrite the
        // account's saved CSV import settings.
        if ($this->format !== 'camt') {
            $account->csv_import_settings = [
                'csv_import_mapping' => $this->mapping,
                'csv_order_reversed' => $this->csvOrderReversed,
            ];
            $account->save();
        }
        $last_id = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first('id')->id ?? 1;

        // In case no saldo is given in CSV, we need to calculate it row by row ourselves
        // first Saldo should be sourced from last entry in DB, if there is no entry, we assume 0
        // we decided to set saldo to 0 if it is the first entry in the DB - alternative would have been to throw an error
        // For CAMT we anchor the running saldo to the statement's opening balance so the stored
        // saldo matches the bank's real figures (it ends exactly on the closing balance). The
        // ContinuityRule has already verified this equals the last stored saldo when one exists.
        $currentBalance = ($this->format === 'camt' && $this->openingBalance !== null)
            ? $this->openingBalance
            : ($account->bankTransactions()->orderBy('id', 'desc')->first()?->saldo ?? 0);

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
            $this->addError('upload', __('konto.csv-import-error'));

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
     * Identity mapping for CAMT: each BankTransaction column maps to the row key of the same
     * name produced by CamtImportParser, so save() reads $row[$mapping[$col]] === $row[$col].
     * saldo is left empty so save() computes the running balance; comment stays unset.
     *
     * @return Collection<string, string>
     */
    private function camtMapping(): Collection
    {
        // saldo is computed in save(); comment is user-only; primanota is a numeric bank batch
        // number with no CAMT equivalent (the account-servicer ref is alphanumeric) — leave them
        // unmapped so they are not written.
        return collect((new BankTransaction)->getFillable())
            ->mapWithKeys(fn ($col) => [$col => in_array($col, ['saldo', 'comment', 'primanota'], true) ? '' : $col]);
    }

    /**
     * @return void gets called if Account Id changes
     */
    public function updatedAccountId(): void
    {
        // CAMT carries no per-account mapping/order; keep the parsed rows and identity mapping.
        if ($this->format === 'camt') {
            $this->resetValidation();

            return;
        }

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
     * Clear the uploaded file and all derived state so the user can re-upload.
     */
    public function clearUpload(): void
    {
        $this->reset(['upload', 'header', 'separator', 'csvOrderReversed', 'format', 'statementIban', 'openingBalance', 'closingBalance']);
        $this->data = collect();
        $this->resetValidation('upload');
        // Restore the current account's CSV mapping (a CAMT upload had replaced it with the
        // identity mapping); reset() has already put $format back to its 'csv' default.
        $this->updatedAccountId();
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
