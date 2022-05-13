<?php

namespace framework\helper;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class EnvSetter
{
    /**
     * @var string[] - linewise content of env (example) file, key is line number starting with 0
     */
    private array $envLines;

    private LoggerInterface $logger;

    public function __construct(private string $envPath, LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->logger->debug('Env @ ', [$this->envPath]);
        if (file_exists($this->envPath)) {
            $envContent = file_get_contents($this->envPath);
        } else {
            throw new RuntimeException('Env not found');
        }
        $this->envLines = explode(PHP_EOL, $envContent);
    }

    private function save(): void
    {
        file_put_contents($this->envPath, implode(PHP_EOL, $this->envLines));
    }

    /**
     * @param string[] $config name => value of parameters to set
     */
    public function setEnvVars(array $config): void
    {
        $varsToSet = array_keys($config);
        foreach ($this->envLines as $lineNumber => $lineContent) {
            $key = $this->compareLine($lineContent, $config);
            if ($key !== false) {
                $this->logger->debug('Key found', [$key]);
                $varName = $varsToSet[$key];
                $this->envLines[$lineNumber] = $this->lineGeneration($varName, $config[$varName]);
                $_ENV[$varName] = $config[$varName];
                unset($varsToSet[$key]); // remove from array keys
            }
        }
        // append rest
        if (!empty($varsToSet)) {
            $this->logger->info('Variables were not in .env before file -> still appended', $varsToSet);
            $this->envLines[] = '### Appended by EnvSetter at ' . date_create()->format(DATE_ATOM);
            foreach ($varsToSet as $varName) {
                $this->envLines[] = $this->lineGeneration($varName, $config[$varName]);
                $_ENV[$varName] = $config[$varName];
            }
        }
        $this->save();
    }

    public function setEnvVar(string $name, string $value): void
    {
        $this->setEnvVars([$name => $value]);
    }

    /**
     * @param string $lineContent content of the given line
     * @param array $needles int => needle name
     * @return int|bool key of found needle - false if none
     */
    private function compareLine(string $lineContent, array $needles): int|bool
    {
        $varName = trim(explode('=', $lineContent)[0]);
        $upperNeedles = array_map('strtoupper', $needles);
        return array_search($varName, $upperNeedles, true);
    }

    private function lineGeneration(string $name, string $value): string
    {
        $name = trim($name);
        $value = trim($value);
        return "$name=\"$value\"";
    }
}
