<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 30.01.19
 * Time: 14:54
 */

abstract class EscFunc{
	
	
	protected function projektLinkEscapeFunction($id, $createdate, $name){
		$year = date("y", strtotime($createdate));
		return $this->renderInternalHyperLink("IP-$year-$id $name", "projekt/$id");
	}
	
	protected function auslagenLinkEscapeFunction($projektId, $auslagenId, $name){
		return $this->renderInternalHyperLink(
			"A$auslagenId " . $this->defaultEscapeFunction($name),
			"projekt/$projektId/auslagen/$auslagenId"
		);
	}
	
	protected function renderInternalHyperLink($text, $dest){
		return "<a href='" . htmlspecialchars(
				URIBASE . $dest
			) . "'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
	}
	
	protected function defaultEscapeFunction($val){
		//default escape-funktion to use if nothing is
		if (empty($val)){
			return "<i>keine Angabe</i>";
		}else{
			return htmlspecialchars($val);
		}
	}
	
	protected function hiddenInputEscapeFunction($name, $value){
		$name = htmlspecialchars($name);
		$value = htmlspecialchars($value);
		return "<input type='hidden' name='$name' value='$value'>";
	}
	
	protected function moneyEscapeFunction($money){
		return number_format($money, 2, ",", " ") . "&nbsp;€";
	}
	
	protected function date2relstrEscapeFunction($time){
		if ($time === "")
			return $this->defaultEscapeFunction("");
		if (!ctype_digit($time))
			$time = strtotime($time);
		
		$diff = strtotime(date("Y-m-d")) - $time;
		
		$past = $diff > 0;
		$diff = abs($diff);
		$anzahlTage = floor($diff / (60 * 60 * 24));
		if ($anzahlTage > 1){
			return ($past ? "vor " : "in ") . $anzahlTage . " Tagen";
		}else if ($anzahlTage === 0){
			return "heute";
		}else{
			return $past ? "gestern" : "morgen";
		}
	}
	
	protected function textAreaEscapeFunction($name, $value, $requiered = false){
		$name = htmlspecialchars($name);
		$value = htmlspecialchars($value);
		$requiered = $requiered ? "required" : "";
		return "<textarea name='$name' rows='1' class='form-control booking__text' $requiered>$value</textarea>";
	}
}