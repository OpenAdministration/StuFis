<?php

namespace App\Http\Controllers\Legacy;

use App\Exceptions\LegacyJsonException;
use App\Exceptions\LegacyRedirectException;
use App\Http\Controllers\Controller;
use forms\projekte\auslagen\AuslagenHandler2;
use framework\DBConnector;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyController extends Controller
{
    public function bootstrap(): void
    {
        require_once base_path('legacy/lib/inc.all.php');
    }

    public function render(Request $request)
    {
        try {
            ob_start();
            $this->bootstrap();
            require base_path('legacy/www/index.php');
            $output = ob_get_clean();

            // if wanted by the unit test the content is delivered without the layout
            if ($request->input('testing')) {
                return $output;
            }

            // otherwise with
            return view('legacy.main', ['content' => $output]);
        } catch (LegacyRedirectException $e) {
            return $e->redirect;
        } catch (LegacyJsonException $e) {
            ob_get_clean();

            return response()->json($e->content);
        } catch (\Exception $exception) {
            // get rid of the already printed html
            ob_get_clean();
            throw $exception;
        }
    }

    public function renderFile($auslagen_id, $hash)
    {
        $db = DBConnector::getInstance()->dbFetchAll('fileinfo', where: ['hashname' => $hash, 'link' => $auslagen_id]);
        $name = $db[0]['filename'] ?? 'error';
        $path = "/auslagen/$auslagen_id/$hash/$name.pdf";

        return view('components.inlineFile', ['src' => $path]);
    }

    public function belegePdf(int $project_id, int $auslagen_id, int $version, ?string $file_name = null)
    {
        // file was generated and requested by the iframe
        if (! empty($file_name)) {
            return \Storage::response(
                "auslagen/$auslagen_id/belege-pdf-v$version.pdf",
                $file_name
            );
        }
        // generate file and iframe to display it
        $this->bootstrap();
        $ah = new AuslagenHandler2([
            'pid' => $project_id,
            'aid' => $auslagen_id,
            'action' => 'belege-pdf',
        ]);
        $ah->generate_belege_pdf();
        $path = route('legacy.belege-pdf', [
            'project_id' => $project_id,
            'auslagen_id' => $auslagen_id,
            'version' => $version,
            'file_name' => "Belege-IP$project_id-A$auslagen_id.pdf",
        ]);

        return view('components.inlineFile', ['src' => $path]);
    }

    public function zahlungsanweisungPdf(int $project_id, int $auslagen_id, int $version, ?string $file_name = null)
    {
        // file was generated and requested by the iframe call
        if (! empty($file_name)) {
            return \Storage::response(
                "/auslagen/$auslagen_id/zahlungsanweisung-v$version.pdf",
                $file_name
            );
        }
        // generate file and iframe to request it
        $this->bootstrap();
        $ah = new AuslagenHandler2([
            'pid' => $project_id,
            'aid' => $auslagen_id,
            'action' => 'zahlungsanweisung-pdf',
        ]);
        $ah->generate_zahlungsanweisung_pdf();
        $path = route('legacy.zahlungsanweisung-pdf', [
            'project_id' => $project_id,
            'auslagen_id' => $auslagen_id,
            'version' => $version,
            'file_name' => "Zahlungsanweisung-IP$project_id-A$auslagen_id.pdf",
        ]);

        return view('components.inlineFile', ['src' => $path]);
    }

    public function deliverFile($auslagen_id, $fileHash, $fileName): StreamedResponse
    {
        $path = "/auslagen/$auslagen_id/$fileHash.pdf";
        if (\Storage::exists($path)) {
            return \Storage::response($path, $fileName);
        }
        throw new FileNotFoundException("Datei $path konnte nicht gefunden werden");
    }
}
