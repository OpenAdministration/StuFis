<?php

namespace App\Console\Commands;

use App\Models\Legacy\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Settings;

class ProjectMailDomainChangeCommand extends Command
{
    protected $signature = 'project:mail-domain-change';

    protected $description = 'Update project responsible emails to match the configured mail domain';

    public function handle(): void
    {
        $domain = Settings::get('mail_domain');

        if (empty($domain)) {
            $this->error('Mail domain is not configured in settings.');
            return;
        }

        $this->info("Checking projects with responsible emails not ending with @{$domain}...");

        // Find projects where responsible doesn't end with the given domain
        $projects = Project::whereNotNull('responsible')
            ->where('responsible', '!=', '')
            ->where('responsible', 'not like', "%@{$domain}")
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No projects found that need updating.');
            return;
        }

        $this->info("Found {$projects->count()} project(s) to update.");

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        foreach ($projects as $project) {
            // Extract the local part before @ or use the whole value if no @
            $localPart = strstr($project->responsible, '@', true) ?: $project->responsible;

            // Update to the new domain
            $project->responsible = $localPart . '@' . $domain;
            $project->save();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Successfully updated all projects!');
    }
}
