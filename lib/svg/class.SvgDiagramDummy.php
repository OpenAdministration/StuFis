<?php
namespace intertopia\Classes\svg;

require_once 'class.SvgDiagramCore.php';

/**
 * Dummy Diagram Class
 * creates dummy image
 *
 * @author 		Michael Gnehr <michael@gnehr.de>
 * @category    framework
 * @package 	intertopia
 * @since 		09.08.2016
 * @version 	02.0.0 since 01.07.2018
 * @copyright 	Copyright (C) 2016-Today - All rights reserved - do not copy or reditribute
 */
class SvgDiagramDummy extends SvgDiagramCore
{
	/**
	 * this class implements following diagram types
	 * @var array
	 */
	private static $types = [
		'Dummy',
		'None',
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
			$this->type = 'None';
		}
		parent::__construct();
		$this->settings['DUMMY'] = [
			'title' => ($this->type=='Dummy')?'Placeholder':'Diagram valid Type Not Found',
			'text' => ($this->type=='Dummy')?'PLACEHOLDER':' NO CHARTTYPE SET',
			'hover' => 'yellow',
		];
	}
	
	// TYPE IMPLEMENTATION --------------------------------------
	
	/**
	 * (non-PHPdoc)
	 * @see \intertopia\Classes\svg\SvgDiagramCore::render()
	 */
	function render(){
		$svg = '<title>'.$this->settings['DUMMY']['title'].'</title>';
		$n = $this->settings['height'] * $this->settings['width'] / 6200;
		$f = 0.091 * sqrt($this->settings['height'] * $this->settings['width']);
		for ($i = 0; $i < round($n); $i++){
			$r = mt_rand(30, 120);
			$x = mt_rand($r, $this->settings['width'] - $r);
			$y = mt_rand($r, $this->settings['height'] - $r);
			$svg .= $this->suroundElementWithMouseHilight($this->drawCircle($x, $y, $r, 'black', '#cccccc', 1, NULL, 0.2), 0.7);
		}
		$text = $this->drawText($this->settings['DUMMY']['text'], $this->settings['width']/2, $this->settings['height']/2, NULL, 'red', 'bold', $f, 0 , 'Helvetica, Arial, sans-serif');
		$svg .= $this->suroundElementWithMouseHilight($text, 0.7, ($this->settings['DUMMY']['hover'])? $this->settings['DUMMY']['hover'] : NULL);
		$this->setSvgResult($svg, true);
	}
}

?>