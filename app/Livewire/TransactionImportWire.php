<?php

namespace App\Livewire;

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use Illuminate\Support\Collection;
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

    public int $account_id;

    public $latestTransaction;

    public $mapping;

    public $data;
    public $header;

    public function mount()
    {

        $this->mapping = $this->createMapping();
        $this->data = collect();
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
        //TODO: gespeichertes Mapping vom letzten Mal anzeigen, falls eins existiert


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

        // create BankTransaction with values from $data, according to the keys assigned in $mapping
        /* $db_entry = array();
        foreach ($this->mapping as $key => $value) // value sollte jetzt der zugeordnete csv header sein
        {
            $db_entry[$key] = $this->data[$this->mapping[$key]];
        }
        BankTransaction::create($db_entry);
        */


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
}
