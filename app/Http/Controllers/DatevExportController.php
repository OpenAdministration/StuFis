<?php

namespace App\Http\Controllers;

use App\Exports\Datev\DatevExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatevExportController extends Controller
{
    /**
     * Stream a previously generated DATEV zip to the browser and delete it afterwards.
     *
     * The zip is served from here rather than returned straight from the Livewire
     * component because Livewire base64-encodes any file response in memory, which blows
     * the PHP memory limit for large (PDF-laden) archives. A BinaryFileResponse streams
     * the file straight off disk instead. The signed URL carries the stored filename, so
     * no session state is needed to bridge the two requests.
     */
    public function download(Request $request): BinaryFileResponse
    {
        Gate::authorize('download', DatevExport::class);

        // basename() keeps the (signed) filename param confined to the export directory.
        $path = 'datev-exports/'.basename((string) $request->query('file'));

        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()
            ->download(Storage::disk('local')->path($path), (string) $request->query('name', 'datev-export.zip'), [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend();
    }
}
