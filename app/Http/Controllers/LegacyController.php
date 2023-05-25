<?php

namespace App\Http\Controllers;

use App\Exceptions\LegacyRedirectException;
use forms\projekte\auslagen\AuslagenHandler2;
use framework\DBConnector;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;

class LegacyController extends Controller
{
    public function bootstrap() : void {
        if (!defined('SYSBASE')) {
            define('SYSBASE', base_path('/legacy/'));
        }
        require_once SYSBASE . '/lib/inc.all.php';
    }

    public function render()
    {
        try {
            ob_start();
            $this->bootstrap();
            require dirname(__FILE__, 4) . '/legacy/www/index.php';
            $output = ob_get_clean();
            return view('legacy.main', ['content' => $output]);
        } catch (LegacyRedirectException $e){
            return $e->redirect;
        } catch (\Exception $exception){
            // get rid of the already printed html
            ob_get_clean();
            throw $exception;
        }
    }

    public function renderFile($auslagen_id, $hash){
        $db = DBConnector::getInstance()->dbFetchAll('fileinfo', where: ['hashname' => $hash, 'link' => $auslagen_id]);
        $name = $db[0]['filename'] ?? 'error';
        $path = "/auslagen/$auslagen_id/$hash/$name.pdf";
        return view('components.inlineFile', ['src' => $path]);
    }

    public function belegePdf(int $project_id, int $auslagen_id, int $version, ?string $file_name = null){
        // file was generated and requested by the iframe
        if (!empty($file_name)){
            return \Storage::response(
                "auslagen/$auslagen_id/belege-pdf-v$version.pdf",
                $file_name
            );
        }
        // generate file and iframe to diplay it
        $this->bootstrap();
        $ah = new AuslagenHandler2([
            'pid' => $project_id,
            'aid' => $auslagen_id,
            'action' => 'belege-pdf',
        ]);
        $ah->generate_belege_pdf();
        $path = route('belege-pdf', [
            'project_id' => $project_id,
            'auslagen_id' => $auslagen_id,
            'version' => $version,
            'file_name' => "Belege-IP$project_id-A$auslagen_id.pdf"
        ]);
        return view('components.inlineFile', ['src' => $path]);
    }

    public function zahlungsanweisungPdf(int $project_id, int $auslagen_id, int $version, ?string $file_name = null){
        // file was generated and requested by the iframe call
        if (!empty($file_name)){
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
        $path = route('zahlungsanweisung-pdf', [
            'project_id' => $project_id,
            'auslagen_id' => $auslagen_id,
            'version' => $version,
            'file_name' => "Zahlungsanweisung-IP$project_id-A$auslagen_id.pdf"
        ]);
        return view('components.inlineFile', ['src' => $path]);
    }

    public function deliverFile($auslagen_id, $fileHash, $fileName) : StreamedResponse
    {
        $path = "/auslagen/$auslagen_id/$fileHash.pdf";
        if (\Storage::exists($path)){
            return \Storage::response($path, $fileName);
        }
        throw new FileNotFoundException("Datei $path konnte nicht gefunden werden");
    }
}
