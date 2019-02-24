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
	
	const ROW_SEPARATOR = PHP_EOL;
	
	
	/**
	 * CSVBuilder constructor.
	 *
	 * @param array  $data
	 * @param array  $header        if header is empty data will be printed without checking
	 * @param string $cellSeparator default is ","
	 * @param string $cellEscape
	 */
	public function __construct(array $data, array $header, $cellSeparator = ",", $cellEscape = "\""){
		$this->data = $data;
		$this->header = $header;
		$this->cellSeparator = $cellSeparator;
		$this->cellEscape = $cellEscape;
		$this->csvString = $this->buildCSV();
	}
	
	private function buildCSV(){
		$ret = [];
		foreach ($this->data as $row){
			$ret[] = $this->buildRow($row);
		}
		return implode(self::ROW_SEPARATOR, $ret);
	}
	
	public function echoCSV($fileName = "", $withRowHeader = true){
		if ($withRowHeader === true){
			$ret = implode($this->cellSeparator, $this->header) . self::ROW_SEPARATOR . $this->csvString;
		}else{
			$ret = $this->csvString;
		}
		if (!empty($fileName)){
			header('Content-type: text/csv');
			header("Content-disposition: attachment;filename=$fileName.csv");
		}
		echo $ret;
		die();
	}
	
	private function buildRow($row){
		$rowArray = [];
		if (!empty($this->header)){
			foreach ($this->header as $key => $name){
				if (isset($row[$key])){
					$rowArray[] = $this->escapeCell($row[$key]);
				}else{
					$rowArray[] = $this->escapeCell("");
				}
			}
		}else{
			foreach ($row as $cell){
				$rowArray[] = $this->escapeCell($cell);
			}
		}
		return implode(",", $rowArray);
	}
	
	private function escapeCell($cell){
		return $this->cellEscape . strip_tags($cell) . $this->cellEscape;
	}
	
}