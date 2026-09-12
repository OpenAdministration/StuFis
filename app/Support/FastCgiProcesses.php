<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * The PHP FastCGI processes of the current Hostsharing user.
 *
 * A FastCGI process keeps the php.ini it started with for its whole lifetime, so a changed
 * configuration only takes effect once the running processes have been replaced. The web
 * server starts a fresh one on the next request, which makes killing them the supported way
 * to apply a new php.ini without root access.
 *
 * The documented one-liner (`pkill -u "$USER" '^php'`) is fine from a shell but not from
 * inside a PHP process: on Hostsharing `artisan` runs as `php8.4`, so the pattern matches the
 * very process issuing the kill along with its parents. This class therefore resolves the
 * candidates itself and drops its own process chain before signalling anything.
 */
class FastCgiProcesses
{
    /**
     * The user whose processes are considered; defaults to the one running this process.
     */
    public function __construct(private readonly string $user = '') {}

    /**
     * PIDs of the user's PHP processes, excluding this process and its ancestors.
     *
     * @return list<int>
     */
    public function pids(): array
    {
        $pgrep = new Process(['pgrep', '-u', $this->user(), '^php']);
        $pgrep->run();

        // pgrep exits 1 when nothing matched, which is a normal outcome, not an error.
        if ($pgrep->getExitCode() !== 0) {
            return [];
        }

        $pids = array_map(intval(...), preg_split('/\s+/', trim($pgrep->getOutput())) ?: []);
        $own = $this->ownProcessChain();

        return array_values(array_filter($pids, fn (int $pid): bool => $pid > 0 && ! in_array($pid, $own, true)));
    }

    /**
     * Terminate the given processes. Returns the number of processes signalled.
     *
     * @param  list<int>  $pids
     */
    public function kill(array $pids): int
    {
        if ($pids === []) {
            return 0;
        }

        $kill = new Process(['kill', ...array_map(strval(...), $pids)]);
        $kill->run();

        return count($pids);
    }

    /**
     * This process and every ancestor up to init - the processes a kill must not touch.
     *
     * @return list<int>
     */
    private function ownProcessChain(): array
    {
        $chain = [];
        $pid = getmypid();

        while (is_int($pid) && $pid > 1 && ! in_array($pid, $chain, true)) {
            $chain[] = $pid;
            $pid = $this->parentPid($pid);
        }

        return $chain;
    }

    /**
     * The parent PID of a process, read from procfs (0 when it cannot be determined).
     */
    private function parentPid(int $pid): int
    {
        $stat = @file_get_contents("/proc/{$pid}/stat");

        if ($stat === false) {
            return 0;
        }

        // Format: "<pid> (<comm>) <state> <ppid> ...". The command name is unquoted and may
        // itself contain spaces and parentheses, so the fields are read after its last ')'.
        $afterComm = strrpos($stat, ')');

        if ($afterComm === false) {
            return 0;
        }

        $fields = preg_split('/\s+/', trim(substr($stat, $afterComm + 1))) ?: [];

        return isset($fields[1]) ? (int) $fields[1] : 0;
    }

    private function user(): string
    {
        return $this->user !== '' ? $this->user : (string) (getenv('USER') ?: get_current_user());
    }
}
