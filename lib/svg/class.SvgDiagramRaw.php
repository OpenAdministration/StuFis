<?php
namespace intertopia\Classes\svg;

require_once 'class.SvgDiagramCore.php';

/**
 * State Diagram Class
 * create svg manually, procide access to core functions
 *
 * @author 		Michael Gnehr <michael@gnehr.de>
 * @category    framework
 * @package 	intertopia
 * @since 		09.08.2016
 * @version 	02.0.0 since 01.07.2018
 * @copyright 	Copyright (C) 2016-Today - All rights reserved - do not copy or reditribute
 */
class SvgDiagramRaw extends SvgDiagramCore
{
	/**
	 * this class implements following diagram types
	 * @var array
	 */
	private static $types = [
		'Raw',
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
	}
	
	// TYPE SETTING / GETTER/SETTER -----------------------
	
	/**
	 * return set of current settings
	 */
	public function getSettings(){
		return $this->settings;
	}
	
	/**
	 * set RAW Settings variables
	 * @param string|number $key array key
	 * @param mixed $value
	 */
	public function setRawSetting($key, $value){
		$this->settings['RAW'][$key] = $value;
	}
	
	/**
	 * @return the $dataset
	 */
	public function getDataset()
	{
		return $this->dataset;
	}
	
	/**
	 * @return the $colorMap
	 */
	public function getColorMap()
	{
		return $this->colorMap;
	}
	
	/**
	 * @return the $translator
	 */
	public function getTranslator()
	{
		return $this->translator;
	}

	/**
	 * @return the $result
	 */
	public function getResult()
	{
		return $this->result;
	}
	
	// TYPE IMPLEMENTATION --------------------------------------
	
	/**
	 * (non-PHPdoc)
	 * @see \intertopia\Classes\svg\SvgDiagramCore::render()
	 */
	function render(){
	}
	
	// ------------------------------------
	
	/**
	 * @param string $result
	 */
	public function setSvgResult($svg, $capsule = false)
	{
		parent::setSvgResult($svg, $capsule);
	}
	
	/**
	 * creates 4 entry array from string or smaller arrays, like css does on padding or margin
	 * @param string $in
	 * @return array
	 */
	public function toCssFourValue($in){
		return parent::toCssFourValue($in);
	}
	
	/* ------ DRAW FUNCTIONS ------ */
	
	/**
	 * generates SVG Element: Text
	 * @param string $str Text to display
	 * @param number $x xPosition (center if NULL)
	 * @param number $y yPosition (center if NULL)
	 * @param string $anchor start|middle|end ('middle' if NULL)
	 * @param string $color
	 * @param string $weight NULL|bold|normal
	 * @param number $size Fontsize
	 * @param number $rotate rotate Text to degree of
	 * @return string
	 */
	public function drawText($str, $x=NULL, $y=NULL, $anchor=NULL, $color=NULL, $weight=NULL, $size=NULL, $rotate=NULL, $family = NULL){
		return parent::drawText($str, $x, $y, $anchor, $color, $weight, $size, $rotate, $family);
	}
	
	/**
	 * generates SVG Element: Horizontal Line
	 * @param number $y yPosition (center if NULL)
	 * @param number $x xPosition (center if NULL)
	 * @param int $length line-length, if NULL Line starts at padding end ends with padding
	 * @param string $title some Browsers show titles as tooltip, can be NULL
	 * @param int $strokeWidth
	 * @param string $color
	 * @param int $padding set padding, if NULL settigns padding is used
	 * @return string
	 */
	public function drawHLine($y, $x=NULL, $length=NULL, $title=NULL, $strokeWidth = 1, $color="#000000", $padding=NULL){
		return parent::drawHLine($y, $x, $length, $title, $strokeWidth, $color, $padding);
	}
	
	/**
	 * generates SVG Element: Vertical Line
	 * @param number $x xPosition (center if NULL)
	 * @param number $y yPosition (center if NULL)
	 * @param int $length line-length, if NULL Line starts at padding end ends with padding
	 * @param string $title some Browsers show titles as tooltip, can be NULL
	 * @param int $strokeWidth
	 * @param string $color
	 * @param int $padding set padding, if NULL settigns padding is used
	 * @return string
	 */
	public function drawVLine($x, $y=NULL, $length=NULL, $title=NULL, $strokeWidth = 1, $color="#000000", $padding=NULL){
		return parent::drawVLine($x, $y, $length, $title, $strokeWidth, $color, $padding);
	}
	
	/**
	 * generates SVG Element: Line
	 * @param number $x1
	 * @param number $y1
	 * @param number $x2
	 * @param number $y2
	 * @param number $strokeWidth
	 * @param String $color
	 * @param string $title some Browsers show titles as tooltip, can be NULL
	 * @return string
	 */
	public function drawLine($x1,$y1,$x2,$y2, $strokeWidth, $color, $title=NULL){
		return parent::drawLine($x1,$y1,$x2,$y2, $strokeWidth, $color, $title);
	}
	
	/**
	 * generates SVG Element: Manhatten Line
	 * @param number $x1 start position
	 * @param number $y1
	 * @param number $x2 end position
	 * @param number $y2
	 * @param number $r radius
	 * @param bool 	 $direction: 0 -> first horizon line ; 1 -> vertival line first
	 * @param number $strokeWidth
	 * @param array|null $arrayStart
	 * @param array|null $arrayEnd
	 * @param String $fill color
	 * @param String $stroke color
	 * @param string $title some Browsers show titles as tooltip, can be NULL
	 * @return string
	 */
	public function drawManhattenLine($x1,$y1,$x2,$y2, $r, $direction = 0, $strokeWidth=1, $arrowStart=NULL, $arrowEnd=NULL, $fill='none', $stroke = 'black', $title=NULL){
		return parent::drawManhattenLine($x1,$y1,$x2,$y2, $r, $direction, $strokeWidth, $arrowStart, $arrowEnd, $fill, $stroke, $title);
	}
	
	/**
	 * generates SVG Element: Line
	 * @param number $x1
	 * @param number $y1
	 * @param number $x2
	 * @param number $y2
	 * @param number $strokeWidth
	 * @param String $color
	 * @param string $title some Browsers show titles as tooltip, can be NULL
	 * @param bool $direction vertical 1 | hoizontal 0
	 * @return string
	 */
	public function drawAutoBez($x1,$y1,$x2,$y2, $strokeWidth, $color, $title=NULL, $direction=0){
		return parent::drawAutoBez($x1,$y1,$x2,$y2, $strokeWidth, $color, $title, $direction);
	}
	
	/**
	 * generates SVG Element: rect with rounded corners and text
	 * @param number $x position
	 * @param number $y position
	 * @param number $width
	 * @param number $height
	 * @param number|array $r radius ; array: first index is top right - clockwise direction
	 * @param string|array $text as string or array with drawText values
	 * @param array $options ['stroke' => 'black', 'fill' => 'white']
	 * @param number $id set tag id
	 * @return string
	 */
	public function drawShape($x, $y, $width, $height, $r, $text = '', $text_offset = 0 , $options = ['stroke' => 'black', 'fill' => 'white'], $id = NULL, $title=NULL) {
		return parent::drawShape($x, $y, $width, $height, $r, $text, $text_offset , $options, $id, $title);
	}
	
	/**
	 * draw path
	 * @param string $p path
	 * @param string $title
	 * @param array $attr
	 * @param string $fill
	 * @param string $stroke
	 * @param string $strokeWidth
	 * @return string
	 */
	public function drawPath($p, $title = NULL, $attr = [], $fill = 'none', $stroke = 'black', $strokeWidth = '1'){
		return parent::drawPath($p, $title, $attr, $fill, $stroke, $strokeWidth);
	}
	
	/**
	 * draw triangle
	 * @param number $x position
	 * @param number $y position
	 * @param number $h height
	 * @param number $a width
	 * @param number $rot rotate
	 * @param string $fill fill color
	 * @param string $stroke stroke color
	 * @param string $strokeWidth stroke width
	 * @param string $title
	 * @return string
	 */
	public function drawTriangle($x,$y,$h,$a,$rot, $fill = 'none', $stroke = 'black', $strokeWidth = 1, $title=NULL){
		return parent::drawTriangle($x,$y,$h,$a,$rot, $fill, $stroke, $strokeWidth, $title);
	}
	
	/**
	 * generates SVG Element: Rect
	 * @param number $lefttopX
	 * @param number $lefttopY
	 * @param number $width
	 * @param number $height
	 * @param string $colorFill
	 * @param string $colorStroke
	 * @param number $strokeWidth
	 * @return string
	 */
	public function drawBar($lefttopX,$lefttopY,$width,$height, $colorFill='red',$colorStroke = 'transparent', $strokeWidth = 1, $title=NULL){
		return parent::drawBar($lefttopX,$lefttopY,$width,$height, $colorFill,$colorStroke, $strokeWidth, $title);
	}
	
	/**
	 * generates SVG Element: Circle/Points
	 * @param number $X
	 * @param number $Y
	 * @param number $radius
	 * @param string $colorFill
	 * @param string $colorStroke
	 * @param number $strokeWidth
	 * @return string
	 */
	public function drawCircle($X,$Y,$radius, $colorFill='red',$colorStroke = 'transparent', $strokeWidth = 1, $title=NULL, $opacity = NULL){
		return parent::drawCircle($X,$Y,$radius, $colorFill,$colorStroke, $strokeWidth, $title, $opacity);
	}
	
	/**
	 * add svg tags with size attributes and hoverscripts (optional)
	 * @param string $svgElements svg elements
	 * @param string $scripts add scripts to svg
	 */
	public function capsuleSvg($svgElements, $scripts = true){
		return parent::capsuleSvg($svgElements, $scripts);
	}
	
	/* ------ JS INTERACTIVE GROUPING ------ */
	
	/**
	 * surrounds elements with <g> tag and adds hover js
	 * @param string $element svg elements
	 * @param number $opacity 0.0 - 1.0
	 * @param string|NULL $bg background color
	 * @return string
	 */
	public function suroundElementWithMouseHilight($element, $opacity = 0.5, $bg = NULL){
		return parent::suroundElementWithMouseHilight($element, $opacity, $bg);
	}
	
	/**
	 * returns svg <defs> element with hover js
	 * @return string
	 */
	public function createHoverScripts(){
		return parent::createHoverScripts();
	}
}

?>