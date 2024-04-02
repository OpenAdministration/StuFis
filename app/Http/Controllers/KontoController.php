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

            $mapping = KontoTransaction::getLabels();

            KontoTransaction::create([
                'id' => $data[0],
                'date' => $data[0],
                'valuta' => $data[0],
                'type' => $data[0],
                'empf_iban' => $data[0],
                'empf_bic' => $data[0],
                'empf_name' => $data[0],
                'primanota' => $data[0],
                'value' => $data[0],
                'saldo' => $data[0],
                'zweck' => $data[0],
                'comment' => $data[0],
                'gvcode' => $data[0],
                'customer_ref' => $data[0]
            ]);
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
