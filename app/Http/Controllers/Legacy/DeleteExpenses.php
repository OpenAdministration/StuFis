<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Legacy\Expenses;
use App\Models\Legacy\ExpensesReceipt;
use App\Models\Legacy\FileInfo;
use framework\auth\AuthHandler;
use Illuminate\Support\Facades\DB;

class DeleteExpenses extends Controller
{
    public function __invoke(int $expense_id)
    {
        $expense = Expenses::findOrFail($expense_id);
        $project = $expense->project;

        // authorize user
        $userPerm =
            AuthHandler::getInstance()->hasGroup('ref-finanzen-hv')
            || $project->creator->id === \Auth::user()->id
            || explode(';', $expense->created)[1] === \Auth::user()->username;
        // authorize state
        $deletableState = ! in_array(explode(';', $expense->state)[0], ['instructed', 'booked'], true);

        if ($userPerm === false || $deletableState === false) {
            abort(403);
        }
        // to make sure to delete everything and not only parts
        \DB::beginTransaction();
        $reciepts = $expense->receipts;
        $reciepts->each(function (ExpensesReceipt $receipt): void {
            // delete all posts
            $receipt->posts()->delete();
            // delete all files db entries (storage later)
            $file_id = $receipt->file_id;
            $fileInfo = FileInfo::find($file_id);
            if ($fileInfo) {
                $fileData = $fileInfo->fileData;
                $fileInfo->delete();
                $fileData->delete();
            }
            // delete receipt itself
            $receipt->delete();
        });

        $expense->delete();

        // clean up storage if DB is successfully cleaned
        DB::afterCommit(function () use ($expense_id): void {
            \Storage::deleteDirectory("auslagen/{$expense_id}/");
        });
        \DB::commit();

        return redirect()->route('legacy.dashboard', ['sub' => 'mygremium']);
    }
}
