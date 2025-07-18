<?php

use App\Http\Controllers\Legacy\LegacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->name('legacy.')->group(function (): void {
    Route::get('menu/hv', [LegacyController::class, 'render'])->name('todo.hv');
    Route::get('menu/kv', [LegacyController::class, 'render'])->name('todo.kv');
    Route::get('menu/belege', [LegacyController::class, 'render'])->name('todo.belege');
    Route::get('menu/stura', [LegacyController::class, 'render'])->name('sitzung');
    Route::get('menu/{sub}', [LegacyController::class, 'render'])->name('dashboard');
    // legacy hhp-picker needs that url schema as a easy forward - route names are here not usable :(
    Route::redirect('konto/{hhp_id}/new', '/bank-account/new');
    Route::get('konto/{hhp?}/{konto?}', [LegacyController::class, 'render'])->name('konto');
    Route::get('konto/credentials', [LegacyController::class, 'render'])->name('konto.credentials');
    Route::get('booking', [LegacyController::class, 'render'])->name('booking');
    Route::get('booking/{hhp_id}/instruct', [LegacyController::class, 'render'])->name('booking.instruct');
    Route::get('booking/{hhp_id}/text', [LegacyController::class, 'render'])->name('booking.text');
    Route::get('booking/{hhp_id}/history', [LegacyController::class, 'render'])->name('booking.history');
    Route::get('hhp/{hhp_id?}', [LegacyController::class, 'render'])->name('hhp');
    Route::get('hhp/{hhp_id}/titel/{titel_id}', [LegacyController::class, 'render']);
    Route::get('projekt/create', [LegacyController::class, 'render'])->name('new-project');

    Route::get('files/get/{auslagen_id}/{hash}', [LegacyController::class, 'renderFile'])->name('get-file');
    Route::get('auslagen/{auslagen_id}/{fileHash}/{filename}.pdf', [LegacyController::class, 'deliverFile']);

    Route::get('projekt/{project_id}/auslagen/{auslagen_id}/version/{version}/belege-pdf/{file_name?}',
        [LegacyController::class, 'belegePdf'])->name('belege-pdf');
    Route::get('projekt/{project_id}/auslagen/{auslagen_id}/version/{version}/zahlungsanweisung-pdf/{file_name?}',
        [LegacyController::class, 'zahlungsanweisungPdf'])->name('zahlungsanweisung-pdf');

    // short link
    Route::redirect('p/{project_id}', '/projekt/{project_id}');
    Route::redirect('a/{auslage_id}', '/auslagen/{auslage_id}');
    Route::get('auslagen/{auslage_id}', static function ($auslage_id) {
        $auslage = \App\Models\Legacy\Expenses::findOrFail($auslage_id);

        return redirect()->to("projekt/$auslage->projekt_id/auslagen/$auslage->id");
    })->name('expense');
    Route::get('titel/{titel_id}', static function ($titel_id) {
        $item = \App\Models\Legacy\LegacyBudgetItem::findOrFail($titel_id);
        $group = $item->budgetGroup;

        return redirect()->to("hhp/$group->hhp_id/titel/$item->id");
    })->name('budget-item');

    // "new" adapted stuff
    Route::get('download/hhp/{hhp_id}/{filetype}', [\App\Http\Controllers\Legacy\ExportController::class, 'budgetPlan']);
    Route::post('project/{project_id}/delete', \App\Http\Controllers\Legacy\DeleteProject::class)->name('project.delete');
    Route::post('expenses/{expenses_id}/delete', \App\Http\Controllers\Legacy\DeleteExpenses::class)->name('expenses.delete');

    // catch all
    Route::any('{path}', [LegacyController::class, 'render'])->where('path', '.*');
});
