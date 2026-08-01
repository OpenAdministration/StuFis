<?php

use Illuminate\Support\Facades\Schedule;

// Activate approved amendments whose activation_date has arrived — see
// App\Console\Commands\stufis\ApplyDueAmendments.
Schedule::command('stufis:apply-due-amendments')->dailyAt('00:01');
