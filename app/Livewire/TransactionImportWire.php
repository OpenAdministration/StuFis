<?php

namespace App\Livewire;

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

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

    public $data;
    public $header;

    public function mount()
    {
        $this->mapping = $this->createMapping();
        $this->data = collect();

        $foo = new BankTransaction();
        foreach($this->mapping as $db_column => $csv_colum){
            $this->db_col_types[$db_column] = DB::getSchemaBuilder()->getColumnType($foo->getTable(), $db_column);
        }
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

    function parseCSV()
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
                return !empty($line);
            })->map(function ($line){
                return str_getcsv($line, $this->separator);
            });

        // get labels for mapping


        // rendern & assign procedure

        // replace mapping values with data keys (csv headers are the new mapping values)

        // saldi abgleich

    }

    public function updatedCsv()
    {
        $this->parseCSV();
    }

    public function save()
    {
        // mapping als vorlage speichern
        $account = BankAccount::findOrFail($this->account_id);
        $account->csv_import_mapping = $this->mapping;
        $account->save();
        $last_id = BankTransaction::where('konto_id', $this->account_id)
            ->orderBy('id', 'desc')->limit(1)->first('id')->id ?? 1;

        // In case no saldo is given in CSV, we need to calculate it row by row
        // first Saldo should be sourced from last entry in DB
        $currentSaldo = $this->latestTransaction->saldo;

        // create BankTransaction with values from $data, according to the keys assigned in $mapping
        DB::beginTransaction();
        foreach ($this->data as $row){
            $transaction = new BankTransaction();
            $transaction->id = ++$last_id;
            $transaction->konto_id = $this->account_id;

            foreach ($this->mapping as $db_col_name => $csv_col_id)
            {
                if(!empty($this->mapping[$db_col_name])){
                    $transaction->$db_col_name = $this->formatDataDb($row[$this->mapping[$db_col_name]], $db_col_name);
                }else{
                    // In case no saldo is given in CSV, we need to calculate it row by row
                    if($db_col_name === 'saldo'){
                        $currentValue = str($row[$this->mapping['value']])->replace(',','.');
                        $currentSaldo = bcadd($currentSaldo, $currentValue, 2);
                        $transaction->$db_col_name = $this->formatDataDb($currentSaldo, $db_col_name);
                    }
                }
            }

            $transaction->save();
        }
        try {
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            $this->addError('csv', 'Nope');
            return;
        }

        return redirect()->route('konto.import.manual')
            ->with(['message' => __('konto.csv-import-success-msg', ['new-saldo' => $currentSaldo])]);
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

    public function updatedAccountId()
    {
        $account = BankAccount::findOrFail($this->account_id);
        $this->latestTransaction = BankTransaction::where('konto_id', $this->account_id)->orderBy('id', 'desc')->limit(1)->first();
        $this->mapping = $this->createMapping($account->csv_import_mapping);
    }

    public function formatDataDb(string|int $value, string $db_col_name): int|string
    {
        $type = $this->db_col_types[$db_col_name];
        return match ($type){
            'integer' => (int) $value,
            'date' => $this->guessCarbon($value, 'Y-m-d'),
            'decimal' => number_format((float) $value, 2, '.', ''),
            default => $value,
        };
    }

    public function formatDataView(string|int $value, string $db_col_name): int|string
    {
        $type = $this->db_col_types[$db_col_name];
        if($db_col_name === "empf_iban") $type = 'iban';
        if($db_col_name === "empf_bic") $type = 'bic';
        return match ($type){
            'date' => $this->guessCarbon($value, 'd.m.Y'),
            'decimal' => number_format((float) $value, 2, ',', '.'),
            'iban' => iban_to_human_format($value),
            default => $value
        };
    }

    /**
     * Guess the format of the input date because banks do not like standards
    */
    private function guessCarbon(string $dateString, string $newFormat) : string
    {
        $formats = ['d.m.y', 'd.m.Y', 'y-m-d', 'Y-m-d', 'jmy', 'jmY', 'dmy', 'dmY'];
        foreach ($formats as $format){#
            try {
                $ret = Carbon::rawCreateFromFormat($format, $dateString);
            }catch (InvalidFormatException $e){
                continue;
            }
            return $ret->format($newFormat);
        }
        return __("Not a date");
    }
}
