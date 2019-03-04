<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 24.02.19
 * Time: 17:52
 */

class CSVBuilder{
	private $data;
	private $header;
	private $csvString;
	private $cellEscape;
	private $cellSeparator;
	private $escapeFormulas;
	private $numberSeparator;
	
	const ROW_SEPARATOR = PHP_EOL;
	
	
	/**
	 * CSVBuilder constructor.
	 *
	 * @param array  $data
	 * @param array  $header        if header is empty data will be printed without checking
	 * @param string $cellSeparator default is ","
	 * @param bool   $escapeFormulars
	 * @param string $cellEscape
	 * @param string $numberSeparator
	 */
	public function __construct(
		array $data, array $header, $cellSeparator = ";", $escapeFormulars = false,
		$cellEscape = "\"", $numberSeparator = ","
	){
		$this->data = $data;
		$this->header = $header;
		$this->cellSeparator = $cellSeparator;
		$this->cellEscape = $cellEscape;
		$this->escapeFormulas = $escapeFormulars;
		$this->numberSeparator = $numberSeparator;
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
		if (is_numeric($cell)){
			return $this->cellEscape . str_replace(".", $this->numberSeparator, $cell) . $this->cellEscape;
		}
		if (is_string($cell)){
			if (substr($cell, 0, 1) === "=" && !$this->escapeFormulas){
				return strip_tags($cell);
			}
		}
		return $this->cellEscape . strip_tags($cell) . $this->cellEscape;
	}
	
}