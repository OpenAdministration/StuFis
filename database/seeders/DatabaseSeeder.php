<?php

namespace Database\Seeders;

use App\Models\BudgetPlan;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\WithFaker;

class DatabaseSeeder extends Seeder
{
    use WithFaker;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        BudgetPlan::factory(5)->populate()->create();

        $this->call(LegacySeeder::class);

        if(\App::isLocal()){
            $this->call(LocalSeeder::class);
        }
    }
}
