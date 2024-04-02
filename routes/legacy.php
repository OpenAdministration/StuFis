<?php


use App\Http\Controllers\Legacy\LegacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'legacy:login'])->name('legacy.')->group(function(){
    Route::get('menu/hv', [LegacyController::class, 'render'])->name('todo.hv');
    Route::get('menu/kv', [LegacyController::class, 'render'])->name('todo.kv');
    Route::get('menu/belege', [LegacyController::class, 'render'])->name('todo.belege');
    Route::get('menu/stura', [LegacyController::class, 'render'])->name('sitzung');
    Route::get('menu/{sub}', [LegacyController::class, 'render'])->name('dashboard');
    Route::get('konto/{hhp?}/{konto?}', [LegacyController::class, 'render'])->name('konto');
    Route::get('booking', [LegacyController::class, 'render'])->name('booking');
    Route::get('booking/{hhp_id}/instruct', [LegacyController::class, 'render'])->name('booking.instruct');
    Route::get('booking/{hhp_id}/text', [LegacyController::class, 'render'])->name('booking.text');
    Route::get('booking/{hhp_id}/history', [LegacyController::class, 'render'])->name('booking.history');
    Route::get('hhp/{id?}', [LegacyController::class, 'render'])->name('hhp');
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

    // "new" stuff
    Route::get('download/hhp/{hhp_id}/{filetype}', [\App\Http\Controllers\Legacy\ExportController::class, 'budgetPlan']);
    Route::post('project/{project_id}/delete', \App\Http\Controllers\Legacy\DeleteProject::class)->name('project.delete');
    Route::post('expenses/{expenses_id}/delete', \App\Http\Controllers\Legacy\DeleteExpenses::class)->name('expenses.delete');

    // catch all
    Route::any('{path}', [LegacyController::class, 'render'])->where('path', '.*');
});
