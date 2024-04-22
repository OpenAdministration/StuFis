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
        $header = null;
        $data = array();

        // csv in array speichern, header sind array header
        foreach ($fileContents as $line) {
            $row = str_getcsv($line);

            // header raus ziehen
            if (!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }

        // hole dbmodel keys und zugeörige translation slugs (aka labels)
        //$mapping = KontoTransaction::getLabels();
        $foo = new KontoTransaction();
        $mapping = $foo->getLabels();

        // render view mit mapping und data
        // TODO livewire
        return view('konto.manual', ['data' => $data, 'mapping' => $mapping]);

            // labels anzeigen inkl translation syntax
                // -> blade view rendern

            // manuelle zuordnung zu $data keys (aka csv header) vornehmen lassen
                // -> von blade view zu controllerfunktion directen

            // mapping values mit data keys überschreiben (sodass jetzt die csv header werte im mapping value stehen)


            // neues Objekt erstellen mit den Werten aus $data, welche zum header gehören, der in $mapping zugeordnet ist
            $db_entry = array();
            foreach ($mapping as $key => $value) // value sollte jetzt der zugeordnete csv header sein
            {
                $db_entry[$key] = $data[$mapping[$key]];
            }
            KontoTransaction::create($db_entry);




        //write the logic here to store csv data in database, header mapping als yaml o.ä. speichern?
        // beim ersten mal alle dropdowns leer lassen, in zukunft die zuletzt zugeordnete zurdnung vorauswählen (?)
        // https://tailwindui.com/components/#product-application-ui

        //return redirect('file-form')->with('status', 'File Has been uploaded successfully in Laravel');

    }

    public function manual(){
        return view('konto.manual', ['data' => NULL]);
    }

//    public function show(){
//        return view('konto.import.manual.show');
//    }
}
