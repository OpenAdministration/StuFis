<?php

namespace framework\cli;

use Ahc\Cli\Application as App;
use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Output\Writer;
use CzProject\GitPhp\GitRepository;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

/**
 * Class UpdateCommand
 * @property $remote
 */
class UpdateCommand extends \Ahc\Cli\Input\Command
{
    private GitRepository $repo;
    private string $remoteBranch;

    public function __construct(App $app = null)
    {
        parent::__construct('update', 'Builds non existent Database tables', false, $app);
        $this->option('--dev', 'Update with dev dependencies');
        $this->argument('[remote]', 'Git remote');

        $this->repo = new GitRepository(SYSBASE);
    }

    // This method is auto called before `self::execute()` and receives `Interactor $io` instance
    public function interact(Interactor $io): void
    {
        $out = new Writer();
        $currentBranch = $this->repo->getCurrentBranchName();
        $out->comment('Current branch: ' . $currentBranch, true);
        if ($this->remote === null) {
            $availableBranches = array_filter(
                $this->repo->getRemoteBranches(),
                static fn ($remoteBranch) => str_ends_with($remoteBranch, $currentBranch)
            );
            $availableBranches = array_values($availableBranches);
            if (count($availableBranches) === 1) {
                $this->remoteBranch = $availableBranches[0];
                $out->info("Selected remote branch $this->remoteBranch", true);
                return;
            }
            $tbl = [];
            foreach ($availableBranches as $key => $branch) {
                $tbl[] = ['id' => $key + 1, 'pull-target' => $branch];
            }
            $out->table($tbl);
            $pick = $io->prompt('Pick one target to pull', 1, function (int $val) use ($availableBranches) {
                if ($val <= 0 || $val > count($availableBranches)) {
                    throw new InvalidArgumentException('Invalid pick');
                }
            });
            $this->remoteBranch = $availableBranches[$pick - 1];
        } else {
            $remoteBranch = $this->remote . '/' . $currentBranch;
            if (in_array($remoteBranch, $this->repo->getRemoteBranches(), true)) {
                $this->remoteBranch = $remoteBranch;
            } else {
                $out->error($remoteBranch . ' does not exist', true);
                $this->unset('remote');
                $this->interact($io);
                return;
            }
        }
    }

    public function execute($dev = null): void
    {
        $dev = isset($dev);
        $out = new Writer();
        $out->info('Update files', true);

        $this->repo->pull(explode('/', $this->remoteBranch)[0]);

        $phpPath = $_SERVER['_']; // @see https://stackoverflow.com/a/3889557/4609612

        if (!file_exists(SYSBASE . '/composer.phar')) {
            $this->installComposer();
        }
        $out->info('Update composer...');
        $composerSelfUpdate = new Process([$phpPath, 'composer', 'self-update']);
        $composerSelfUpdate->run();

        $composerParams = [$phpPath, SYSBASE . '/composer.phar', 'install'];
        if ($dev !== true) {
            $composerParams[] = '--no-dev';
        }
        $composer = new Process($composerParams);
        $out->info('Install dependencies...', true);
        $composer->run();
        echo $composer->getOutput();
        echo $composer->getErrorOutput();

        $out->info('Installing database', true);
        (new DbBuildCommand())->execute();
    }

    public function installComposer(): void
    {
        $phpPath = $_SERVER['_']; // @see https://stackoverflow.com/a/3889557/4609612
        $out = new Writer();
        copy('https://getcomposer.org/installer', SYSBASE . '/composer-setup.php');
        $composerSetup = new Process([$phpPath, SYSBASE . '/composer-setup.php']);
        $out->info('Install composer...', true);
        $composerSetup->run();
        echo $composerSetup->getOutput();
        unlink(SYSBASE . '/composer-setup.php');
    }
}
