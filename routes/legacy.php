<?php


use App\Http\Controllers\LegacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'legacy:login'])->group(function(){
    Route::get('menu/{sub}/', [LegacyController::class, 'render'])->name('menu');
    Route::get('konto/{hhp?}/{konto?}', [LegacyController::class, 'render'])->name('konto');
    Route::get('booking', [LegacyController::class, 'render'])->name('booking');
    Route::get('hhp', [LegacyController::class, 'render'])->name('hhp');
    Route::get('projekt/create', [LegacyController::class, 'render'])->name('new-project');

    Route::get('files/get/{auslagen_id}/{hash}', [LegacyController::class, 'renderFile'])->name('get-file');
    Route::get('auslagen/{auslagen_id}/{fileHash}/{filename}.pdf', [LegacyController::class, 'deliverFile']);

    Route::get('projekt/{project_id}/auslagen/{auslagen_id}/version/{version}/belege-pdf/{file_name?}',
        [LegacyController::class, 'belegePdf'])->name('belege-pdf');
    Route::get('projekt/{project_id}/auslagen/{auslagen_id}/version/{version}/zahlungsanweisung-pdf/{file_name?}',
        [LegacyController::class, 'zahlungsanweisungPdf'])->name('zahlungsanweisung-pdf');

    Route::get('auslagen/{auslage_id}', static function ($auslage_id){
        $auslage = \App\Models\Legacy\Expenses::find($auslage_id);
        return redirect()->to("projekt/$auslage->projekt_id/auslagen/$auslage->id");
    });
    // catch all
    Route::any('{path}', [LegacyController::class, 'render'])->where('path', '.*');
});
