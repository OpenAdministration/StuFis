<?php

namespace App\Livewire;

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\User;
use App\Rules\CsvTransactionImport\BalanceRule;
use App\Rules\CsvTransactionImport\IbanRule;
use App\Rules\CsvTransactionImport\MoneyRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Regex\Regex;

class TransactionImportWire extends Component
{
    use WithFileUploads;

    #[Validate('required|mimes:csv,txt|max:2048')]
    public $csv;

    public $separator;
    public $csvFileEncoding;

    #[Url]
    public $account_id;

    public $latestTransaction;

    public $mapping;
    public $db_col_types;

    /** @var Collection */
    public $data;
    public $header;

    // CSV entries in order = -1, in reverse order = 1
    public $csvOrder = -1;


    public function mount()
    {
        $this->authorize('cashOfficer', User::class);

        $this->mapping = $this->createMapping();
        $this->data = collect();

        $foo = new BankTransaction();
        foreach($this->mapping as $db_column => $csv_colum){
            $this->db_col_types[$db_column] = DB::getSchemaBuilder()->getColumnType($foo->getTable(), $db_column);
        }
    }

    public function rules() : array
    {
        // mapping has only the csv column numbers as values, so we need to work around a bit,
        // we only check if a matching column was given, the values of this columns only in special cases
        return [
            'mapping.date' => 'required|int',
            'mapping.valuta' => 'required|int',
            'mapping.type' => 'required|int',
            'mapping.value' => [
                'required', 'int',
                new MoneyRule($this->data->pluck($this->mapping->get('value')))
            ],
            'mapping.saldo' => [
                'int',
                new MoneyRule($this->data->pluck($this->mapping->get('saldo'))),
                //new MoneyRule($this->data->pluck($this->mapping->get('value'))),
                new BalanceRule(
                    $this->data->pluck($this->mapping->get('value')),
                    $this->data->pluck($this->mapping->get('saldo')),
                    $this->latestTransaction?->saldo
                )
            ],
            'mapping.empf_name' => 'required|int',
            'mapping.empf_bic' => 'sometimes|int',
            'mapping.empf_iban' => [
                'required', 'int',
                new IbanRule($this->data->pluck($this->mapping->get('empf_iban')))
            ],
            'mapping.zweck' => 'required|int'
        ];
    }

    /**
     * Fills up the mapping array with missing column keys (and empty values)
     * @param array $merger
     * @return Collection
     */
    private function createMapping(array $merger = []) : Collection
    {
        $foo = new BankTransaction();
        $emptyMapping = collect(array_flip(array_keys($foo->getLabels())));
        return $emptyMapping->map(function ($value, $key) use ($merger){
            return $merger[$key] ?? "";
        });
    }

    public function parseCSV() : void
    {
        $this->validateOnly('csv');
        // temp save uploaded file
        $this->csv->store();
        $content = $this->csv->get();

        // check for windows excel file encoding, transform to utf-8
        $winEncoding = mb_check_encoding($content, 'Windows-1252');
        if($winEncoding){
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        // explode content in lines
        $content = str($content);
        $lines = $content->explode(PHP_EOL);

        // guess csv separator
        $amountComma = $content->substrCount(',');
        $amountSemicolon = $content->substrCount(';');
        $this->separator = $amountSemicolon > $amountComma ? ";" : ",";

        // extract header and data, explode data with csv separator guesses above
        $this->header = str_getcsv($lines->first(), $this->separator);
        $this->data = $lines->except(0)
            ->filter(function ($line){
                return !(empty($line) || Regex::match('/^(,*|;*)\r?\n?$/', $line)->hasMatch());
            })->map(function ($line){
                return str_getcsv($line, $this->separator);
            })->map(function ($lineArray){
                // normalize data
                foreach ($lineArray as $key => $cell){
                    // tests
                    $moneyTest = Regex::match('/(\-?)([0-9]+),([0-9]{1,2})/', $cell);

                    // conversions
                    if($moneyTest->hasMatch()){
                        // group 1: sign, group 2: money before delimiter, group 3: cents after delimiter
                        $lineArray[$key] = $moneyTest->group(1) . $moneyTest->group(2) . '.' . $moneyTest->group(3);
                    }
                }
                return $lineArray;
            });

        // get labels for mapping

        // rendern & assign procedure

        // replace mapping values with data keys (csv headers are the new mapping values)

        // saldi abgleich

    }

    public function updatedCsv(): void
    {
        $this->parseCSV();
    }

    public function save()
    {
        $this->validate();
        // mapping als vorlage speichern
        $account = BankAccount::findOrFail($this->account_id);
        $account->csv_import_settings = [
            "csv_import_mapping" => $this->mapping,
            "csv_order" => $this->csvOrder
        ];
        $account->save();
        $last_id = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first('id')->id ?? 1;

        // In case no saldo is given in CSV, we need to calculate it row by row
        // first Saldo should be sourced from last entry in DB, if there is no entry, we assume 0
        $currentBalance = $this->latestTransaction->saldo ?? 0; // alternative to 0: throw error

        // create BankTransaction with values from $data, according to the keys assigned in $mapping
        DB::beginTransaction();
        foreach ($this->data as $row){
            $transaction = new BankTransaction();
            $transaction->id = ++$last_id;
            $transaction->konto_id = $this->account_id;

            foreach ($this->mapping as $db_col_name => $csv_col_id) {
                if(!empty($this->mapping[$db_col_name])){
                    $transaction->$db_col_name = $this->formatDataDb($row[$this->mapping[$db_col_name]], $db_col_name);
                }else if($db_col_name === 'saldo'){
                    $currentValue = str($row[$this->mapping['value']])->replace(',','.');
                    $currentBalance = bcadd($currentBalance, $currentValue, 2);
                    $transaction->$db_col_name = $this->formatDataDb($currentBalance, $db_col_name);
                }
            }

            $transaction->save();
        }
        try {
            DB::commit();
        } catch (\Exception $exception){
            DB::rollBack();
            $this->addError('csv', 'Nope');
            return;
        }

        $newBalance = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first()->saldo;

        return redirect()->route('konto.import.manual')
            ->with(['message' => __('konto.csv-import-success-msg', ['new-saldo' => $newBalance, 'transaction-amount' => $this->data->count()])]);
    }

    public function render() : \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\View\View
    {
        $accounts = BankAccount::all();
        $labels = (new BankTransaction())->getLabels();
        return view('livewire.bank.csv-import', [
            'accounts' => $accounts,
            'firstNewTransaction' => $this->data->first(),
            'lastNewTransaction' => $this->data->last(),
            'labels' => $labels,
        ]);
    }

    public function updatedAccountId() : void
    {
        $account = BankAccount::findOrFail($this->account_id);
        $this->latestTransaction = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first();
        $this->mapping = $this->createMapping($account->csv_import_settings["csv_import_mapping"] ?? []);
        $this->csvOrder = (int) ($account->csv_import_settings["csv_order"] ?? -1);
    }

    /**
     * is called when mapping got updated
     * @return void
     */
    public function updatedMapping() : void
    {
        $this->validate();
        // date, value, saldo
    }

    public function formatDataDb(string|int $value, string $db_col_name): int|string
    {
        $type = $this->db_col_types[$db_col_name];
        //if($type === 'decimal') dd([$value, (float) $value,$db_col_name]);
        return match ($type){
            'integer' => (int) $value,
            'date' => guessCarbon($value, 'Y-m-d'),
            'decimal' => $value, // no casting needed, string is expected
            default => $value,
        };
    }

    public function formatDataView(string|int $value, string $db_col_name): int|string
    {
        $type = $this->db_col_types[$db_col_name];
        if($db_col_name === "empf_iban") $type = 'iban';
        if($db_col_name === "empf_bic") $type = 'bic';
        return match ($type){
            'date' => guessCarbon($value, 'd.m.Y'),
            'decimal' => number_format((float) $value, 2, ',', '.') . ' €',
            'iban' => iban_to_human_format($value),
            default => $value
        };
    }

    /**
     * Change the order of CSV entries in current upload
     * @return void
     */
    public function reverseCsvOrder(): void
    {
        $this->data = $this->data->reverse();
        $this->csvOrder *= -1;
        $this->validate();
    }

    public function isCsvOrderReversed() : bool
    {
        return $this->csvOrder === -1;
    }
}
