<?php

namespace framework\render;

abstract class EscFunc{
	
	
	protected function projektLinkEscapeFunction($id, $createdate, $name) : string
    {
		$year = date("y", strtotime($createdate));
		return $this->internalHyperLinkEscapeFunction("IP-$year-$id $name", "projekt/$id");
	}
	
	protected function auslagenLinkEscapeFunction($projektId, $auslagenId, $name) : string
    {
		return $this->internalHyperLinkEscapeFunction(
			"A$auslagenId " . $this->defaultEscapeFunction($name),
			"projekt/$projektId/auslagen/$auslagenId"
		);
	}
	
	protected function internalHyperLinkEscapeFunction($text, $dest) : string
    {
		return "<a href='" . htmlspecialchars(
				URIBASE . $dest
			) . "'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
	}
	
	protected function defaultEscapeFunction($val) : string
    {
		//default escape-funktion to use if nothing is
		if ($val === "null" || empty($val)){
			return "<i>keine Angabe</i>";
		}
        return htmlspecialchars($val);
    }
	
	protected function hiddenInputEscapeFunction($name, $value) : string
    {
		$name = htmlspecialchars($name);
		$value = htmlspecialchars($value);
		return "<input type='hidden' name='$name' value='$value'>";
	}
	
	protected function moneyEscapeFunction($money) : string
    {
		return number_format($money, 2, ",", "&nbsp;") . "&nbsp;€";
	}
	
	protected function date2relstrEscapeFunction($time) : string
    {
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
	
	protected function textAreaEscapeFunction($name, $value, $required = false) : string
    {
		$name = htmlspecialchars($name);
		$value = htmlspecialchars($value);
		$required = $required ? "required" : "";
		return "<textarea name='$name' rows='1' class='form-control booking__text' $required>$value</textarea>";
	}

	protected function arrayToListEscapeFunction($array) : string
    {
	    $out = "<ul>";
	    foreach ($array as $item){
	        $out .= "<li>$item</li>";
        }
	    $out .= "</ul>";
	    return $out;
    }
}