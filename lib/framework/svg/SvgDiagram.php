<?php
namespace framework\svg;


/**
 * factory class for diagram generation
 * Factory
 * @author 		Michael Gnehr <michael@gnehr.de>
 * @category    framework
 * @package 	intertopia
 * @since 		09.08.2016
 * @version 	02.0.0 since 01.07.2018
 * @copyright 	Copyright (C) 2016-Today - All rights reserved - do not copy or reditribute
 */
class SvgDiagram
{
	/* ------ DIAGRAM TYPES ------ */
	/**
	 * class constansts -> diagramm type: BLOCK
	 * @var string
	 */
	const TYPE_BLOCK = 'Block';
	/**
	 * class constansts -> diagramm type: ADDING BEAM (BLOCK)
	 * @var string
	 */
	const TYPE_ADDINGBLOCK = 'AddingBeam';
	/**
	 * class constansts -> diagramm type: PIE
	 * @var string
	 */
	const TYPE_PIE = 'Pie';
	/**
	 * class constansts -> diagramm type: LINE
	 * @var string
	 */
	const TYPE_LINE = 'Line';
	/**
	 * class constansts -> diagramm type: STATE
	 * @var string
	 */
	const TYPE_STATE = 'State';
	/**
	 * class constansts -> diagramm type: RAW
	 * draw svg manually
	 * @var string
	 */
	const TYPE_RAW = 'Raw';
	/**
	 * class constansts -> diagramm type: DUMMY
	 * @var string
	 */
	const TYPE_DUMMY = 'Dummy';
	/**
	 * class constansts -> diagramm type: NONE
	 * default value
	 * @var string
	 */
	const TYPE_NONE = 'None';
	
	// private member variables -------------------------------
	
	/**
	 * list of valid diagram types
	 * @var array
	 */
	private static $types = [
		'SvgDiagramDummy' 		=> 'Dummy',
		'SvgDiagramNone' 		=> 'Dummy',
		'SvgDiagramPlaceholder' => 'Dummy',
		'SvgDiagramBlock' 		=> 'Block',
		'SvgDiagramAddingBeam'  => 'AddingBeam',
		'SvgDiagramPie' 		=> 'Pie',
		'SvgDiagramLine' 		=> 'Line',
		'SvgDiagramState' 		=> 'State',
		'SvgDiagramRaw' 		=> 'Raw',
	];
		
	/**
	 * private constructor
	 */
	private function __construct()
	{
	}
	
	/**
	 * return list of diagram types
	 * @return array
	 */
	public static function getTypes(): array
    {
		$out = [];
		foreach (self::$types as $t){
			$out[$t] = $t;
		}
		return array_values($out);
	}
	
	/**
	 * return Svg Diagram by type name
	 * @param string Svg$type
	 * @return SvgDiagramCore or inherited class object
	 */
	public static function newDiagram($type): ?SvgDiagramCore
    {
		if (!is_string($type)) {
            return NULL;
        }
		if (mb_strpos($type, 'SvgDiagram')===0){
			$type = str_replace('SvgDiagram', '', $type);
		}
		$c = 'SvgDiagramDummy';
		if (isset(self::$types['SvgDiagram' .$type])){
			$c = 'SvgDiagram' . self::$types['SvgDiagram' .$type];
		} else {
			$type = 'None';
		}
		require_once 'class.'.$c.'.php';
		$c = 'intertopia\\Classes\\svg\\'.$c;
		return new $c($type);
	}
}

?>