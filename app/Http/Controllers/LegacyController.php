<?php

namespace App\Http\Controllers;

use App\Exceptions\LegacyRedirectException;
use App\View\Components\InlineFile;
use App\View\Components\Layout;
use framework\DBConnector;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyController extends Controller
{
    public function render()
    {
        try {
            ob_start();
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

    public function deliverFile($auslagen_id, $fileHash, $fileName) : StreamedResponse
    {
        $path = "auslagen/$auslagen_id/$fileHash.pdf";
        return \Storage::response($path, $fileName);
    }
}
