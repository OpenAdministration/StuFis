<?php
namespace intertopia\Classes\svg;

require_once 'class.SvgDiagramBlock.php';

/**
 * Line (Block) Diagram Class
 * this class is an alias for SvgDiagramBlock
 *
 * @author 		Michael Gnehr <michael@gnehr.de>
 * @category    framework
 * @package 	intertopia
 * @since 		09.08.2016
 * @version 	02.0.0 since 01.07.2018
 * @copyright 	Copyright (C) 2016-Today - All rights reserved - do not copy or reditribute
 */
class SvgDiagramLine extends SvgDiagramBlock
{
	/**
	 * this class implements following diagram types
	 * @var array
	 */
	private static $types = [
		'Line',
	];
	
	// CLASS CONSTRUCTOR --------------------------------------
	
	/**
	 * constructor
	 * @param string $type
	 */
	function __construct($type)
	{
		if (in_array($type, self::$types)){
			$this->type = $type;
		} else {
			$this->type = self::$types[0];
		}
		parent::__construct();
		$this->settings['LINE'] = [
			'pointRadius' => 6,
			'line-width' => 2,
			'ignore_null' => false,
		];
	}
	
	// TYPE SETTING  --------------------------------------
	
	/**
	 * set Settings variables
	 * @param string|number $key : 'pointRadius'|'line-width'
	 * @param mixed $value
	 */
	public function setLineSetting($key, $value){
		if (array_key_exists($key, $this->settings['LINE'])){
			$this->settings['LINE'][$key] = $value;
		}
	}
}

?>