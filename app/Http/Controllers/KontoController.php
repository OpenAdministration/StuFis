<?php

namespace App\Http\Controllers;

use App\Models\Konto;
use App\Models\Legacy\KontoTransaction;
use Illuminate\Http\Request;

class KontoController extends Controller
{

    public function index(){
        //$data = BudgetPlan::orderByDesc('start_date')->get();
        //return view('budget-plan.index', ['plans' => $plans]);
        return view('konto.import.index');
    }

    function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, null, $delimiter)) !== false)
            {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        return $data;
    }

    public function store(Request $request)
    {

        $validatedData = $request->validate([
         'file' => 'required|mimes:csv|max:2048',
        ]);

        //$path = $request->file('file')->store('public/files');
        // muss nicht gestored werden, nur gelesen und in array / db gespeichert

        $file = $request->file('file');
        $fileContents = file($file->getPathname());

        foreach ($fileContents as $line) {
            $data = str_getcsv($line);

            // bevor wir die Zeile als Transaktion parsen, sollten wir die Zeile in ein Array geben, anzeigen, und den Nutzer zuordnen lassen
            $mapping = [
                'date' => null,
                'valuta' => null,
                'type' => null,
                'empf_iban' => null,
                'empf_bic' => null,
                'empf_name' => null,
                'primanota' => null
            ];
/*
            \Schema::getColumnListing((new KontoTransaction())->getTable());


            foreach(KontoTransaction->getAttributes() as $key => $value){

            }
*/
            // hole dbmodel keys und zugeörige translation slugs (aka labels)
            $mapping = KontoTransaction::getLabels();

            // labels anzeigen inkl translation syntax
                // -> blade view rendern

            // manuelle zuordnung zu $data keys (aka csv header) vornehmen lassen
                // -> von blade view zu controllerfunktion directen

            // mapping values mit data keys überschreiben (sodass jetzt die csv header werte im mapping value stehen)


            // neues Objekt erstellen mit den Werten aus $data, welche zum header gehören, der in $mapping zugeordnet ist
            $db_entry = array();
            foreach ($mapping as $key => $value)
            {
                $db_entry[$key] = $data[$mapping[$key]];
            }
            KontoTransaction::create($db_entry);

/*
            KontoTransaction::create([
                'date' => $data[$mapping['date']],
                'valuta' => $data[$mapping['valuta']],
                'type' => $data[$mapping['type']],
                'empf_iban' => $data[$mapping['empf_iban']],
                'empf_bic' => $data[$mapping['empf_bic']],
                'empf_name' => $data[$mapping['empf_name']],
                'primanota' => $data[$mapping['primanota']],
                'value' => $data[$mapping['value']],
                'saldo' => $data[$mapping['saldo']],
                'zweck' => $data[$mapping['zweck']],
                'comment' => $data[$mapping['comment']],
                'gvcode' => $data[$mapping['gvcode']],
                'customer_ref' => $data[$mapping['customer_ref']]
            ]);
*/
        }

        //write the logic here to store csv data in database, header mapping als yaml o.ä. speichern?
        // beim ersten mal alle dropdowns leer lassen, in zukunft die zuletzt zugeordnete zurdnung vorauswählen (?)
        // https://tailwindui.com/components/#product-application-ui

        return redirect('file-form')->with('status', 'File Has been uploaded successfully in Laravel');

    }

    public function manual(){
        return view('konto.manual', ['data' => NULL]);
    }

//    public function show(){
//        return view('konto.import.manual.show');
//    }
}
