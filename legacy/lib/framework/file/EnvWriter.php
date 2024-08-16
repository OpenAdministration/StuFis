<?php

namespace framework\file;

/**
 * TODO: unit testing - seems not to work so far :/
 */
class EnvWriter
{
    /**
     * @var array key: varName value: line number (beginning at 0)
     */
    private array $keys;

    /**
     * @var string[] key: lineNumber value: lineContent
     */
    private array $lines;

    public function __construct(private string $envFilename = '.env')
    {
        $content = file_get_contents(SYSBASE.$envFilename);
        $this->lines = explode(PHP_EOL, $content);
        $keys = $this->lines;
        array_walk($keys, static function ($line) {
            if (str_starts_with($line, '#')) {
                return '';
            }

            return trim(explode('=', $line)[0]);
        });
        $this->keys = array_flip($keys);
    }

    /**
     * @param  string  $comment  without #
     * @param  array  $keyValueParis  key: name value: value
     */
    public function addVarBlock(string $comment, array $keyValueParis): void
    {
        $this->addLine($comment, true);
        foreach ($keyValueParis as $key => $value) {
            $key = strtoupper($key);
            $this->addLine($this->composeLine($key, $value));
        }
    }

    public function set(string $key, string $value): void
    {
        $key = strtoupper($key);
        $line = $this->composeLine($key, $value);

        if (isset($this->keys[$key])) {
            $number = $this->keys[$key];
            $this->lines[$number] = $line;
        } else {
            $this->addLine($line);
        }
    }

    private function composeLine(string $key, string $value): string
    {
        $key = strtoupper($key);

        return "$key=$value";
    }

    public function addLine(string $content, bool $isComment = false): void
    {
        if ($isComment) {
            $content = '# '.$content;
        }
        $this->lines[] = $content;
    }

    public function save(): void
    {
        $content = implode(PHP_EOL, $this->lines);
        file_put_contents(SYSBASE.$this->envFilename, $content);
    }
}
