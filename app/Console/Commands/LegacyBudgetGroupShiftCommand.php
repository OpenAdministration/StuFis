<?php

namespace App\Console\Commands;

use App\Models\Legacy\LegacyBudgetGroup;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyBudgetGroupShiftCommand extends Command
{
    protected $signature = 'legacy:budget-group-shift
        {new_group_id : The new Group ID}';

    protected $description = 'Insert an new budget group with the given id in the newest BudgetPlan';

    public function handle(): void
    {
        \DB::transaction(function () {
            $latestPlan = LegacyBudgetPlan::all()->last();
            $budgetGroups = LegacyBudgetGroup::where('hhp_id', $latestPlan->id)
                ->where('id', '>=', $this->argument('new_group_id'));
            $this->info('The following amount of other groups will be shifted back: '.$budgetGroups->count());
            \Schema::disableForeignKeyConstraints();
            // this is so hacky ...
            $budgetGroups->update(['id' => DB::raw('-(id + 1)')]);
            LegacyBudgetGroup::where('id', '<', 0)->update(['id' => DB::raw('-id')]);
            // but its needed, otherwise there is a duplicate key. id's should not be used for sorting...
            LegacyBudgetItem::where('hhpgruppen_id', '>=', $this->argument('new_group_id'))
                ->update(['hhpgruppen_id' => DB::raw('hhpgruppen_id + 1')]);
            $newGroup = new LegacyBudgetGroup([
                'hhp_id' => $latestPlan->id,
                'gruppen_name' => $this->ask('Please enter new Group name:'),
                'type' => $this->ask('Is it Einname or Ausgabe? E/A') === 'E' ? 0 : 1,
            ]);
            $newGroup->id = $this->argument('new_group_id');
            $newGroup->save();
            \Schema::enableForeignKeyConstraints();

        });
    }
}
