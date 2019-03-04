<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 24.02.19
 * Time: 17:52
 */

class CSVBuilder{
	const LANG_DE = 1;
	const LANG_EN = 2;
	private $data;
	private $header;
	private $csvString;
	private $cellEscape;
	private $cellSeparator;
	private $escapeFormulas;
	private $lang;
	
	const ROW_SEPARATOR = PHP_EOL;
	
	
	/**
	 * CSVBuilder constructor.
	 *
	 * @param array  $data
	 * @param array  $header        if header is empty data will be printed without checking
	 * @param string $cellSeparator default is ","
	 * @param bool   $escapeFormulars
	 * @param string $cellEscape
	 * @param int    $lang
	 */
	public function __construct(
		array $data, array $header, $cellSeparator = ";", $escapeFormulars = false,
		$cellEscape = "\"", $lang = self::LANG_DE
	){
		$this->data = $data;
		$this->header = $header;
		$this->cellSeparator = $cellSeparator;
		$this->cellEscape = $cellEscape;
		$this->escapeFormulas = $escapeFormulars;
		$this->lang = $lang;
		$this->csvString = $this->buildCSV();
	}
	
	private function buildCSV(){
		$ret = [];
		foreach ($this->data as $row){
			$ret[] = $this->buildRow($row);
		}
		return implode(self::ROW_SEPARATOR, $ret);
	}
	
	public function echoCSV($fileName = "", $withRowHeader = true, $encoding = "WINDOWS-1252"){
		if (!empty($fileName)){
			header('Content-type: text/csv');
			header("Content-disposition: attachment;filename=$fileName.csv");
		}
		echo $this->getCSV($withRowHeader, $encoding);
		die();
	}
	
	public function getCSV($withRowHeader = true, $encoding = "WINDOWS-1252"){
		if ($withRowHeader === true){
			$ret = implode($this->cellSeparator, $this->header) . self::ROW_SEPARATOR . $this->csvString;
		}else{
			$ret = $this->csvString;
		}
		
		return mb_convert_encoding($ret, $encoding, "UTF-8");
	}
	
	private function buildRow($row){
		$rowArray = [];
		if (!empty($this->header)){
			foreach ($this->header as $key => $name){
				if (array_key_exists($key, $row)){
					$rowArray[] = $this->escapeCell($row[$key]);
				}else{
					if (DEV){
						$rowArray[] = $this->escapeCell(var_export($row, true));
					}else{
						$rowArray[] = $this->escapeCell("error-by-export");
					}
				}
			}
		}else{
			foreach ($row as $cell){
				$rowArray[] = $this->escapeCell($cell);
			}
		}
		return implode($this->cellSeparator, $rowArray);
	}
	
	private function escapeCell($cell){
		if (is_numeric($cell) && $this->lang === self::LANG_DE){
			return $this->cellEscape . str_replace(".", ",", strip_tags($cell)) . $this->cellEscape;
		}
		if (is_string($cell)){
			if (substr($cell, 0, 1) === "="){
				if ($this->lang === self::LANG_DE){
					$cell = strtolower($cell);
					$cell = str_replace("if", "wenn", $cell);
					$cell = str_replace("sum", "summe", $cell);
					$cell = str_replace("sumif", "summewenn", $cell);
					$cell = str_replace("count", "zählen", $cell);
					$cell = str_replace("countif", "zählenwenn", $cell);
				}
				if (!$this->escapeFormulas){
					return strip_tags($cell);
				}
			}
		}
		return $this->cellEscape . strip_tags($cell) . $this->cellEscape;
	}
	
}