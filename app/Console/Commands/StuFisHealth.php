<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StuFisHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stufis:health {--json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives debug info for health';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $output = collect([
            'version' => config('stufis.version', ''),
            'database-prefix' => \DB::connection()->getConfig('prefix'),
        ]);
        if ($this->option('json')) {
            $this->output->writeln(json_encode($output, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } else {
            $tableOutput = [];
            foreach ($output as $key => $value) {
                $tableOutput[] = [$key, $value];
            }
            $this->output->table(['Name', 'Value'], $tableOutput);
        }
    }

    public function gatherHsData() {}
}
