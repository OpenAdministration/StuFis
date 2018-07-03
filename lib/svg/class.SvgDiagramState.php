<?php
namespace intertopia\Classes\svg;

require_once 'class.SvgDiagramCore.php';

/**
 * State Diagram Class
 *
 * @author 		Michael Gnehr <michael@gnehr.de>
 * @category    framework
 * @package 	intertopia
 * @since 		09.08.2016
 * @version 	02.0.0 since 01.07.2018
 * @copyright 	Copyright (C) 2016-Today - All rights reserved - do not copy or reditribute
 */
class SvgDiagramState extends SvgDiagramCore
{
	/**
	 * this class implements following diagram types
	 * @var array
	 */
	private static $types = [
		'State',
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
		$this->settings['STATE'] = [
			'gridsize' 		=> ['x' => 0, 'y' => 0],
			'margin' 		=> ['x' => 40, 'y' => 15],
			'boxsize' 		=> ['w' => 120, 'h' => 30, 'r' => 5],
			'arrowsize' 	=> ['h' => 6, 'a' => 5],
			'childpadding' 	=> 5,
			'childmargin' 	=> 3,
			'boxColor' 		=> ['fill' => '#cccccc', 'stroke' => 'black', 'cursor-pointer' => true],
			'center_lines' 	=> true,
			'force_same_level_line' => true,
			'arrows' 		=> true,
		];
	}
	
	// TYPE SETTING / GETTER/SETTER -----------------------
	
	/**
	 * set State Settings variables
	 * @param string|number $key : 'gridsize'|'arrows'|'force_same_level_line'|'center_lines'|'boxColor'|'margin'|'boxsize'|'arrowsize'|'childpadding'|'childmargin'
	 * @param mixed $value
	 */
	public function setStateSetting($key, $value){
		if (array_key_exists($key, $this->settings['STATE'])){
			$this->settings['STATE'][$key] = $value;
		}
	}
	
	// TYPE IMPLEMENTATION --------------------------------------
	
	/**
	 * (non-PHPdoc)
	 * @see \intertopia\Classes\svg\SvgDiagramCore::render()
	 */
	function render(){
		// settings
		$chartcontent = '';
		$gridsize = $this->settings['STATE']['gridsize'];
		$margin = $this->settings['STATE']['margin'];
		$boxsize = $this->settings['STATE']['boxsize'];
		$arrowsize = $this->settings['STATE']['arrowsize'];
		$childpadding = $this->settings['STATE']['childpadding'];
		$childmargin = $this->settings['STATE']['childmargin'];
		$boxColor = $this->settings['STATE']['boxColor'];
		$center_lines = $this->settings['STATE']['center_lines'];
		$force_same_level_line = $this->settings['STATE']['force_same_level_line'];
		$arrows = $this->settings['STATE']['arrows'];
		 
		$boxes = [];
		//collect information
		$maxy = $this->settings['padding'];
		foreach ($this->dataset as $level){
			$gridsize['y']++;
			$current_y = $gridsize['y'] - 1;
			$current_y = $maxy;
			foreach ($level as $pos => $e){
				$boxes[$e['state']]['level'] = $current_y;
				$boxes[$e['state']]['index'] = $pos;
				$boxes[$e['state']]['state'] = $e['state'];
				$boxes[$e['state']]['data'] = $e;
				$boxes[$e['state']]['x'] = $this->settings['padding'] + $pos * $margin['x'] + $pos * $boxsize['w'];
				$boxes[$e['state']]['y'] = $current_y;
				//offsets
				if (isset($e['offset']['x'])) $boxes[$e['state']]['x'] = $boxes[$e['state']]['x'] + $e['offset']['x'];
				if (isset($e['offset']['y'])) $boxes[$e['state']]['y'] = $boxes[$e['state']]['y'] + $e['offset']['y'];
				$boxes[$e['state']]['w'] = $boxsize['w'];
				$boxes[$e['state']]['out'] = isset($e['target'])? $e['target'] : [];
				$boxes[$e['state']]['children'] = isset($e['children'])? $e['children'] : [];
				$boxes[$e['state']]['title'] = $e['title'];
				$boxes[$e['state']]['options'] = $boxColor;
				if (isset($e['options'])&&is_array($e['options'])){
					foreach ($e['options'] as $kk => $vv){
						$boxes[$e['state']]['options'][$kk] = $vv;
					}
				}
				$boxes[$e['state']]['hovertitle'] = (isset($e['hovertitle'])&&$e['hovertitle'])? $e['hovertitle']:NULL;
				$boxes[$e['state']]['h'] = $boxsize['h'] + count($boxes[$e['state']]['children']) * $boxsize['h'];
				$maxy = max($maxy, $current_y+$boxes[$e['state']]['h']);
				$boxes[$e['state']]['in_pos'] = 0;
				$boxes[$e['state']]['out_pos'] = 0;
				//in counter
				foreach ($boxes[$e['state']]['out'] as $target){
					$boxes[$target]['in'] = ((isset($boxes[$target]['in']))? $boxes[$target]['in'] : 0) + 1;
				}
				 
				$gridsize['x'] = max($gridsize['x'], $pos+1);
				if (isset($e['children'])) $gridsize['y']+= count($e['children']);
			}
			$maxy += $margin['y'];
		}
		 
		// DRAW
		foreach ($boxes as $s => $b){
			if (!isset($b['x'])) continue;
			$boxelement = '';
			//draw boxes
			$text = (isset($b['options']['text']))?$b['options']['text']:[];
			$text['text'] = $b['title'];
			$opt = $b['options'];
			if (isset($opt['trigger']) && $opt['trigger']){
				$opt['onclick'] = 'triggerEvent(\'state-change\', \''.$b['state'].'\')';
			}
			$boxelement.=$this->drawShape(
				$b['x'], $b['y'], $b['w'], $b['h'], 5,
				$text, (count($b['children']))? -$b['h']/2 + $boxsize['h']/2 : 0,
				$opt, NULL, $b['hovertitle']);
		
			//draw children
			$cpos = 0;
			foreach ($b['children'] as $child){
				$xx = $b['x'] + $childpadding;
				$ww = $b['w'] - 2*$childpadding;
				$hh = $b['h'] - 2*$childpadding - $boxsize['h'];
				$hh = $hh / count($b['children']) - $childmargin;
				$yy = $b['y'] + $boxsize['h'] + $childpadding + $cpos * ($hh + $childmargin);
				$options = $b['options'];
				if (isset($child['options'])&&is_array($child['options'])){
					foreach ($child['options'] as $kk => $vv){
						$options[$kk] = $vv;
					}
				}
				if (isset($options['trigger']) && $options['trigger']){
					$options['onclick'] = 'triggerEvent(\'state-change\', \''.$child['state'].'\')';
				}
				$text_c = (isset($options['text']))?$options['text']:[];
				$text_c['text'] = $child['title'];
				$boxelement.=$this->drawShape($xx, $yy, $ww, $hh, 5, $text_c,0, $options, NULL, 
					((isset($child['hovertitle'])&&$child['hovertitle'])?$child['hovertitle']:NULL));
				$cpos++;
			}
		
			//draw arrows
			foreach ($boxes[$s]['out'] as $next){
				//direct line
				if (!isset($boxes[$next]['x'])) continue;
				if ((count($b['out']) == $boxes[$next]['in'] || $force_same_level_line) && $b['level'] == $boxes[$next]['level']){
					// outpos
					$outpos_y = (!$center_lines)? $b['y'] + ($boxes[$s]['out_pos']+1) * $b['h'] / (count($b['out'])+1)
									: $b['y'] + $b['h'] / 2;
					if ($force_same_level_line) $outpos_y = $b['y'] + $boxsize['h'] / 2;
					$inpos_y = (!$center_lines)? $boxes[$next]['y'] + ($boxes[$next]['in_pos']+1) * $boxes[$next]['h'] / ($boxes[$next]['in']+1)
									: $boxes[$next]['y'] + $boxes[$next]['h'] / 2;
					if ($force_same_level_line) $inpos_y = $boxes[$next]['y'] + $boxsize['h'] / 2;
					$outpos_x = $b['x'] + (($b['index']<$boxes[$next]['index'])?$b['w']:0);
					$inpos_x = $boxes[$next]['x'] + (($b['index']>$boxes[$next]['index'])?$boxes[$next]['w']:0);
					
					
					
					$boxelement .= $this->drawAutoBez($outpos_x, $outpos_y, $inpos_x, $inpos_y, 1, 'black');
					$boxes[$s]['out_pos'] = $boxes[$s]['out_pos'] + 1;
					$boxes[$next]['in_pos'] = $boxes[$next]['in_pos'] + 1;
					//triangle
					if ($arrows) {
						$boxelement .= $this->drawTriangle($inpos_x, $inpos_y, (($b['index']<$boxes[$next]['index'])?$arrowsize['h']:-$arrowsize['h']), $arrowsize['a'], 0, 'black');
					}
				//levelshift right side out
				} elseif ($b['index'] < $boxes[$next]['index']) {
					$outpos_y = (!$center_lines)? $b['y'] + ($boxes[$s]['out_pos']+1) * $b['h'] / (count($b['out'])+1)
								: $b['y'] + $b['h'] / 2;
					$outpos_x = $b['x'] + $b['w'];
					$inpos_x = $outpos_x + $margin['x'];
					$inpos_y = (!$center_lines)? $boxes[$next]['y'] + ($boxes[$next]['in_pos']+1) * $boxes[$next]['h'] / ($boxes[$next]['in'] + 1)
								: $boxes[$next]['y'] + $boxes[$next]['h'] / 2;
		
					//tmp
					$boxelement .= $this->drawAutoBez($outpos_x, $outpos_y, $inpos_x, $inpos_y, 1, 'black');
					$boxes[$s]['out_pos'] = $boxes[$s]['out_pos'] + 1;
					$boxes[$next]['in_pos'] = $boxes[$next]['in_pos'] + 1;
		
					//line after levelshift
					if ($inpos_x != $boxes[$next]['x']){
						$boxelement .= $this->drawLine($inpos_x, $inpos_y, $boxes[$next]['x'], $inpos_y, 1, 'black');
						//triangle
						if ($arrows) {
							$boxelement .= $this->drawTriangle($boxes[$next]['x'], $inpos_y, $arrowsize['h'], $arrowsize['a'], 0, 'black');
						}
					} else {
						//triangle
						if ($arrows) {
							$chartcontent .= $this->drawTriangle($inpos_x, $inpos_y, $arrowsize['h'], $arrowsize['a'], (($inpos_y < $outpos_y && ($outpos_y - $inpos_y) > $boxsize['h'])? -15: (($inpos_y > $outpos_y && ($inpos_y - $outpos_y) > $boxsize['h'])? +15: 0)), 'black');
						}
					}
				//arrows up down
				} else if ($b['index'] == $boxes[$next]['index']) {
					$outpos_y = $b['y'] + (($b['level'] > $boxes[$next]['level'])? 0 : $b['h']);
					$inpos_y = $boxes[$next]['y'] + (($b['level'] > $boxes[$next]['level'])? $boxes[$next]['h'] : 0);
					$outpos_x = $b['x'] + $b['w']/2;
					$inpos_x = $boxes[$next]['x'] + $boxes[$next]['w']/2;
					//tmp
					$boxelement .= $this->drawAutoBez($outpos_x, $outpos_y, $inpos_x, $inpos_y, 1, 'black', NULL, 1);
					$boxes[$s]['out_pos'] = $boxes[$s]['out_pos'] + 1;
					$boxes[$next]['in_pos'] = $boxes[$next]['in_pos'] + 1;
					if ($arrows) {
						$chartcontent .= $this->drawTriangle($inpos_x, $inpos_y, $arrowsize['h'], $arrowsize['a'], (($b['level'] > $boxes[$next]['level'])? -90 : 90), 'black');
					}
				// lines back to left -> manhatten
				} else if ($b['index'] > $boxes[$next]['index'] && $b['level'] != $boxes[$next]['level']){
					$outpos_y = $b['y'] + (($b['level'] > $boxes[$next]['level'])? 0 : $b['h']);
					$outpos_x = $b['x'] + $b['w']/2;
					$inpos_x = $boxes[$next]['x'] + $boxes[$next]['w'];
					$inpos_y = (!$center_lines)? $boxes[$next]['y'] + ($boxes[$next]['in_pos']+1) * $boxes[$next]['h'] / ($boxes[$next]['in'] + 1)
					: $boxes[$next]['y'] + $boxes[$next]['h'] / 2;
					$boxes[$next]['in_pos'] = $boxes[$next]['in_pos'] + 1;
					$chartcontent .= $this->drawManhattenLine($outpos_x, $outpos_y, $inpos_x, $inpos_y, 10, 1, 1, NULL, $arrows? $arrowsize: NULL );
				}
			}
			$hoverOpt = [
				'opacity' => isset($b['options']['hover']['opacity'])? $b['options']['hover']['opacity']: '0.8',
				'background-color' => isset($b['options']['hover']['background-color'])? $b['options']['hover']['background-color']: NULL,
			];
			$hoverOpt['background-color'] = 'red';
			$chartcontent.=$this->suroundElementWithMouseHilight($boxelement, $hoverOpt['opacity'], $hoverOpt['background-color']);
		}
		$this->setSvgResult($chartcontent, true);
	}
}

?>