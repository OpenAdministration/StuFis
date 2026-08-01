<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Activate approved Nachtragshaushaltspläne (amendments) whose effective_date has arrived —
// see App\Console\Commands\stufis\ApplyDueAmendments.
Schedule::command('stufis:apply-due-amendments')->dailyAt('04:00');
