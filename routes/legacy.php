<?php

use App\Http\Controllers\Legacy\LegacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->name('legacy.')->group(function (): void {
    Route::get('menu/hv', [LegacyController::class, 'render'])->name('todo.hv');
    Route::get('menu/kv', [LegacyController::class, 'render'])->name('todo.kv');
    Route::get('menu/kv/exportBank', [LegacyController::class, 'render'])->name('todo.kv.bank');
    Route::get('menu/belege', [LegacyController::class, 'render'])->name('todo.belege');
    Route::get('menu/stura', [LegacyController::class, 'render'])->name('sitzung');
    Route::get('menu/{sub}', [LegacyController::class, 'render'])->name('dashboard');
    // legacy hhp-picker needs that url schema as a easy forward - route names are here not usable :(
    Route::redirect('konto/{hhp_id}/new', '/bank-account/new');
    Route::get('konto/{hhp_id?}/{konto_id?}', [LegacyController::class, 'render'])->name('konto');
    Route::get('konto/credentials', [LegacyController::class, 'render'])->name('konto.credentials');
    Route::get('konto/credentials/new', [LegacyController::class, 'render'])->name('konto.credentials.new');
    Route::any('konto/credentials/{credential_id}/login', [LegacyController::class, 'render'])->name('konto.credentials.login');
    Route::get('konto/credentials/{credential_id}/tan-mode', [LegacyController::class, 'render'])->name('konto.credentials.tan-mode');
    Route::get('konto/credentials/{credential_id}/sepa', [LegacyController::class, 'render'])->name('konto.credentials.sepa');
    Route::get('konto/credentials/{credential_id}/{short_iban}', [LegacyController::class, 'render'])->name('konto.credentials.import-transactions');
    Route::get('konto/credentials/{credential_id}/{short_iban}/import', [LegacyController::class, 'render'])->name('konto.credentials.import-konto');
    Route::get('booking', [LegacyController::class, 'render'])->name('booking');
    Route::get('booking/{hhp_id}/instruct', [LegacyController::class, 'render'])->name('booking.instruct');
    Route::get('booking/{hhp_id}/text', [LegacyController::class, 'render'])->name('booking.text');
    Route::get('booking/{hhp_id}/history', [LegacyController::class, 'render'])->name('booking.history');
    Route::get('hhp', [LegacyController::class, 'render'])->name('hhp');
    Route::get('hhp/import', [LegacyController::class, 'render'])->name('hhp.import');
    Route::get('hhp/{hhp_id}', [LegacyController::class, 'render'])->name('hhp.view');
    Route::get('hhp/{hhp_id}/titel/{titel_id}', [LegacyController::class, 'render'])->name('hhp.titel.view');
    Route::get('projekt/create', [LegacyController::class, 'render'])->name('new-projekt');
    Route::get('projekt/{projekt_id}', [LegacyController::class, 'render'])->name('projekt');
    Route::get('projekt/{projekt_id}/auslagen/{auslagen_id}', [LegacyController::class, 'render'])->name('expense-long');

    Route::get('files/get/{auslagen_id}/{hash}', [LegacyController::class, 'renderFile'])->name('get-file');
    Route::get('auslagen/{auslagen_id}/{fileHash}/{filename}.pdf', [LegacyController::class, 'deliverFile']);

    Route::get('projekt/{projekt_id}/auslagen/{auslagen_id}/version/{version}/belege-pdf/{file_name?}',
        [LegacyController::class, 'belegePdf'])->name('belege-pdf');
    Route::get('projekt/{projekt_id}/auslagen/{auslagen_id}/version/{version}/zahlungsanweisung-pdf/{file_name?}',
        [LegacyController::class, 'zahlungsanweisungPdf'])->name('zahlungsanweisung-pdf');

    // short link
    Route::redirect('p/{projekt_id}', '/projekt/{projekt_id}');
    Route::redirect('a/{auslagen_id}', '/auslagen/{auslagen_id}');
    Route::get('auslagen/{auslagen_id}', static function ($auslage_id) {
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
    Route::post('projekt/{projekt_id}/delete', \App\Http\Controllers\Legacy\DeleteProject::class)->name('projekt.delete');
    Route::post('expenses/{expenses_id}/delete', \App\Http\Controllers\Legacy\DeleteExpenses::class)->name('expenses.delete');

    // catch all
    Route::any('{path}', [LegacyController::class, 'render'])
        ->where('path', '.*')
        ->name('catch-all');
});
