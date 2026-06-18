<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        // The local auth provider returns committee names 1:1 (e.g. 'Students Council').
        // 'raw' mode passes those through without intersecting the gremien superset,
        // which otherwise (in 'filter' mode) drops them entirely.
        Setting::set('user.committees.mode', 'raw');
    }
}
