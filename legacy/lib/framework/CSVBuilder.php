<?php

namespace framework;

use App\Exceptions\LegacyDownloadException;

class CSVBuilder
{
    public const LANG_DE = 1;

    public const LANG_EN = 2;

    private $data;

    private $header;

    private $csvString;

    private $cellEscape;

    private $cellSeparator;

    private $escapeFormulas;

    private $lang;

    public const ROW_SEPARATOR = PHP_EOL;

    /**
     * CSVBuilder constructor.
     *
     * @param  array  $header  if header is empty() data will be printed without checking
     */
    public function __construct(
        array $data, array $header, string $cellSeparator = ';', bool $escapeFormulars = false,
        string $cellEscape = '"', int $lang = self::LANG_DE
    ) {
        $this->data = $data;
        $this->header = $header;
        $this->cellSeparator = $cellSeparator;
        $this->cellEscape = $cellEscape;
        $this->escapeFormulas = $escapeFormulars;
        $this->lang = $lang;
        $this->csvString = $this->buildCSV();
    }

    private function buildCSV(): string
    {
        $ret = [];
        foreach ($this->data as $row) {
            $ret[] = $this->buildRow($row);
        }

        return implode(self::ROW_SEPARATOR, $ret);
    }

    /**
     * Hands the CSV over as a download and unwinds out of the legacy renderer - it never returns.
     *
     * LegacyController wraps whatever a page buffered in the app layout, so the file has to leave
     * as a response instead of being echoed into that buffer.
     */
    public function echoCSV($fileName = '', $withRowHeader = true, $encoding = 'WINDOWS-1252'): never
    {
        // The body is converted to $encoding below - say so, or Laravel labels it utf-8
        $headers = ['Content-Type' => 'text/csv; charset='.strtolower($encoding)];
        if (! empty($fileName)) {
            $headers['Content-Disposition'] = 'attachment; filename="'.$fileName.'.csv"';
        }

        throw new LegacyDownloadException(
            response($this->getCSV($withRowHeader, $encoding), 200, $headers)
        );
    }

    public function getCSV($withRowHeader = true, $encoding = 'WINDOWS-1252'): string
    {
        if ($withRowHeader === true) {
            $ret = implode($this->cellSeparator, $this->header).self::ROW_SEPARATOR.$this->csvString;
        } else {
            $ret = $this->csvString;
        }

        return mb_convert_encoding($ret, $encoding, 'UTF-8');
    }

    private function buildRow($row): string
    {
        $rowArray = [];
        if (! empty($this->header)) {
            foreach ($this->header as $key => $name) {
                if (array_key_exists($key, $row)) {
                    $rowArray[] = $this->escapeCell($row[$key]);
                } elseif (DEV) {
                    $rowArray[] = $this->escapeCell(var_export($row, true));
                } else {
                    $rowArray[] = $this->escapeCell('error-by-export');
                }
            }
        } else {
            foreach ($row as $cell) {
                $rowArray[] = $this->escapeCell($cell);
            }
        }

        return implode($this->cellSeparator, $rowArray);
    }

    private function escapeCell(string $cell): string
    {
        if (is_numeric($cell) && $this->lang === self::LANG_DE) {
            return $this->cellEscape.str_replace('.', ',', strip_tags($cell)).$this->cellEscape;
        }
        if (! empty($cell) && $cell[0] === '=') {
            switch ($this->lang) {
                case self::LANG_DE:
                    $cell = strtolower($cell);
                    $cell = str_replace(['if(', 'sum(', 'sumif(', 'count(', 'countif('], ['wenn(', 'summe(', 'summewenn(', 'zählen(', 'zählenwenn('], $cell);
                    break;
                case self::LANG_EN:
                default:
            }
            if (! $this->escapeFormulas) {
                return strip_tags($cell);
            }
        }

        return $this->cellEscape.strip_tags($cell).$this->cellEscape;
    }
}
