<?php

loadForms();

function loadForms() {
  global $formulare;

  $handle = opendir(SYSBASE."/config/formulare");

  while (false !== ($entry = readdir($handle))) {
    if (substr($entry, -4) !== ".php") continue;
    require SYSBASE."/config/formulare/".$entry;
  }

  closedir($handle);

}

function getFormConfig($type, $revision) {
  global $formulare;

  if (!isset($formulare[$type])) return false;
  if (!isset($formulare[$type][$revision])) return false;

  return $formulare[$type][$revision];
}

function getFormName($name) {
  $matches = [];
  if (preg_match("/^formdata\[([^\]]*)\](.*)/", $name, $matches)) {
    return $matches[1].$matches[2];
  }
  return false;
}

function getFormValue($name, $type, $values, $defaultValue = false) {
  $name = getFormName($name);
  if ($name === false)
    return $defaultValue;

  foreach($values as $row) {
    if ($row["fieldname"] != $name)
      continue;
    if ($row["contenttype"] != $type) {
      add_message("Feld $name: erwarteter Typ = \"$type\", erhaltener Typ = \"{$row["contenttype"]}\"");
      continue;
    }
    return $row["value"];
  }
  return $defaultValue;
}

function getFormFile($name, $values) {
  $name = getFormName($name);
  if ($name === false)
    return false;

  foreach($values as $row) {
    if ($row["fieldname"] != $name)
      continue;
    return $row;
  }
  return false;
}

function getFormFiles($name, $values) {
  $name = getFormName($name);
  if ($name === false)
    return false;

  $ret = [];
  foreach($values as $row) {
    if (substr($row["fieldname"], 0, strlen($name)) != $name)
      continue;
    $ret[] = $row;
  }
  return $ret;
}

function newTemplatePattern($ctrl, $value) {
  $tPattern = "<placeholder:".uniqid()."/>";
  $ctrl["_render"]->templates[$tPattern] = $value;
  return $tPattern;
}

function renderForm($meta, $ctrl = false) {

  $ctrl["_render"] = new stdClass();
  $ctrl["_render"]->displayValue = false;
  $ctrl["_render"]->inputValue = false;
  $ctrl["_render"]->templates = [];
  $ctrl["_render"]->parentMap = []; /* map currentName => parentName */
  $ctrl["_render"]->currentParent = false;
  $ctrl["_render"]->postHooks = []; /* e.g. ref-field */

  ob_start();
  foreach ($meta as $item) {
    renderFormItem($item, $ctrl);
  }
  $txt = ob_get_contents();
  ob_end_clean();

  foreach($ctrl["_render"]->postHooks as $hook) {
    $hook($ctrl);
  }

  $txt = str_replace(array_keys($ctrl["_render"]->templates), array_values($ctrl["_render"]->templates), $txt);

  echo $txt;
}

function renderFormItem($meta,$ctrl = false) {

  if (!isset($meta["id"])) {
    echo "Missing \"id\" in ";
    print_r($meta);
    die();
  }

  if (!isset($meta["opts"]))
   $meta["opts"] = [];

  if (!isset($ctrl["wrapper"])) {
    $wrapper = "div";
  } else {
    $wrapper = $ctrl["wrapper"];
    unset($ctrl["wrapper"]);
  }

  if (isset($ctrl["class"]))
    $classes = $ctrl["class"];
  else
    $classes = [];

  if (isset($meta["width"]))
    $classes[] = "col-xs-{$meta["width"]}";

  $ctrl["id"] = $meta["id"];
  $ctrl["name"] = "formdata[{$meta["id"]}]";

  if (!isset($ctrl["suffix"]))
   $ctrl["suffix"] = [];
  foreach($ctrl["suffix"] as $suffix) {
    $ctrl["name"] .= "[{$suffix}]";
    if ($suffix !== false) {
      $ctrl["id"] .= $suffix;
    }
  }
  $ctrl["id"] = str_replace(".", "-", $ctrl["id"]);

  $cls = ["form-group"];
  if (in_array("hasFeedback", $meta["opts"])) $cls[] = "has-feedback";

  $myParent = $ctrl["_render"]->currentParent;
  if ($myParent !== false)
    $ctrl["_render"]->parentMap[$ctrl["name"]] = $myParent;
  $ctrl["_render"]->currentParent = $ctrl["name"];

  ob_start();
  switch ($meta["type"]) {
    case "h1":
    case "h2":
    case "h3":
    case "h4":
    case "h5":
    case "h6":
    case "plaintext":
      $isEmpty = renderFormItemPlainText($meta,$ctrl);
      break;
    case "group":
      $isEmpty = renderFormItemGroup($meta,$ctrl);
      break;
    case "text":
    case "email":
    case "url":
      $isEmpty = renderFormItemText($meta,$ctrl);
      break;
    case "money":
      $isEmpty = renderFormItemMoney($meta,$ctrl);
      break;
    case "textarea":
      $isEmpty = renderFormItemTextarea($meta,$ctrl);
      break;
    case "select":
    case "ref":
      $isEmpty = renderFormItemSelect($meta,$ctrl);
      break;
    case "date":
      $isEmpty = renderFormItemDate($meta,$ctrl);
      break;
    case "daterange":
      $isEmpty = renderFormItemDateRange($meta,$ctrl);
      break;
    case "table":
      $isEmpty = renderFormItemTable($meta,$ctrl);
      break;
    case "file":
      $isEmpty = renderFormItemFile($meta,$ctrl);
      break;
    case "multifile":
      $isEmpty = renderFormItemMultiFile($meta,$ctrl);
      break;
    default:
      ob_end_flush();
      echo "<pre>"; print_r($meta); echo "</pre>";
      die("Unkown form element meta type: ".$meta["type"]);
  }
  $txt = ob_get_contents();
  ob_end_clean();

  $ctrl["_render"]->currentParent = $myParent;

  echo "<$wrapper class=\"".implode(" ", $classes)."\">";

  if ($isEmpty !== false) {
    echo "<div class=\"".join(" ", $cls)."\">";
    echo "<input type=\"hidden\" value=\"{$meta["type"]}\" name=\"formtype[".htmlspecialchars($meta["id"])."]\"/>";

    if (isset($meta["title"]) && isset($meta["id"]))
      echo "<label class=\"control-label\" for=\"{$ctrl["id"]}\">".htmlspecialchars($meta["title"])."</label>";
    elseif (isset($meta["title"]))
      echo "<label class=\"control-label\">".htmlspecialchars($meta["title"])."</label>";

    echo $txt;

    if (!in_array("no-form", $ctrl["render"]))
      echo '<div class="help-block with-errors"></div>';
    echo "</div>";
  }

  if (isset($meta["width"]))
    echo "</$wrapper>";
  else
    echo "</$wrapper>";

}

function renderFormItemPlainText($meta, $ctrl) {
  $value = $meta["value"];
  $value = htmlspecialchars($value);
  $value = implode("<br/>", explode("\n", $value));
  switch ($meta["type"]) {
    case "h1":
    case "h2":
    case "h3":
    case "h4":
    case "h5":
    case "h6":
      $elem = $meta["type"];
      break;
    default:
      $elem = "div";
  }
  $tPattern = newTemplatePattern($ctrl, $value);
  echo "<${elem}>{$tPattern}</${elem}>";
}

function renderFormItemGroup($meta, $ctrl) {
  if (in_array("well", $meta["opts"]))
     echo "<div class=\"well\">";

  foreach ($meta["children"] as $child) {
    renderFormItem($child, $ctrl);
  }
  if (in_array("well", $meta["opts"]))
    echo "<div class=\"clearfix\"></div></div>";
}

function renderFormItemText($meta, $ctrl) {
  global $nonce, $URIBASE;

  if (in_array("no-form", $ctrl["render"])) {
    echo "<div class=\"form-control\">";
    $value = "";
    if (isset($ctrl["_values"])) {
      $value = getFormValue($ctrl["name"], $meta["type"], $ctrl["_values"]["_inhalt"], $value);
    }
    $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));
    if ($meta["type"] == "email" && !empty($value))
      echo "<a href=\"mailto:{$tPattern}\">";
    if ($meta["type"] == "url" && !empty($value))
      echo "<a href=\"{$tPattern}\" target=\"_blank\">";
    echo $tPattern;
    if ($meta["type"] == "email" && !empty($value))
      echo "</a>";
    if ($meta["type"] == "url" && !empty($value))
      echo "</a>";
    echo "</div>";
    $ctrl["_render"]->inputValue = $value;
    $ctrl["_render"]->displayValue = htmlspecialchars($value);
    return;
  }

  echo "<input class=\"form-control\" type=\"{$meta["type"]}\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  if (isset($meta["placeholder"]))
    echo " placeholder=\"".htmlspecialchars($meta["placeholder"])."\"";
  if (in_array("required", $meta["opts"]))
    echo " required=\"required\"";
  if (isset($meta["minLength"]))
    echo " data-minlength=\"".htmlspecialchars($meta["minLength"])."\"";
  if (isset($meta["maxLength"]))
    echo " maxlength=\"".htmlspecialchars($meta["maxLength"])."\"";
  if (isset($meta["pattern"]))
    echo " pattern=\"".htmlspecialchars($meta["pattern"])."\"";
  if (isset($meta["pattern-error"]))
    echo " data-pattern-error=\"".htmlspecialchars($meta["pattern-error"])."\"";
  if ($meta["type"] == "email") {
    echo " data-remote=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=validate.email&nonce=".urlencode($nonce))."\"";
    echo " data-remote-error=\"Ungültige eMail-Adresse\"";
  }
  if (isset($meta["prefill"])) {
    $value = "";
    if ($meta["prefill"] == "user:mail")
      $value = getUserMail();
    $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));
    echo " value=\"{$tPattern}\"";
  }
  echo "/>";
  if (in_array("hasFeedback", $meta["opts"]))
    echo '<span class="glyphicon form-control-feedback" aria-hidden="true"></span>';
}

function getFileLink($file, $antrag) {
  global $URIBASE;
  $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/anhang/".$file["id"];
  return "<a href=\"".htmlspecialchars($target)."\">".htmlspecialchars($file["filename"])."</a>";
}

function renderFormItemFile($meta, $ctrl) {
  if (in_array("no-form", $ctrl["render"])) {
    echo "<div class=\"form-control\">";
    $file = false;
    if (isset($ctrl["_values"])) {
      $file = getFormFile($ctrl["name"], $ctrl["_values"]["_anhang"]);
    }
    if ($file) {
      $html = getFileLink($file, $ctrl["_values"]);
      echo newTemplatePattern($ctrl, $html);
      $ctrl["_render"]->displayValue = $html;
    } else {
      $ctrl["_render"]->displayValue = "";
    }
    echo "</div>";
    return;
  }
  echo "<div class=\"single-file-container\">";
  echo "<input class=\"form-control single-file\" type=\"file\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"/>";
  echo "</div>";
}

function renderFormItemMultiFile($meta, $ctrl) {
  if (in_array("no-form", $ctrl["render"])) {
    if (isset($meta["destination"])) return false; // no data here

    echo "<div class=\"form-control\">";
    $files = false;
    if (isset($ctrl["_values"])) {
      $files = getFormFiles($ctrl["name"], $ctrl["_values"]["_anhang"]);
    }
    $ctrl["_render"]->displayValue = "";
    if (is_array($files)) {
      $html = [];
      foreach($files as $file) {
        $html[] = getFileLink($file, $ctrl["_values"]);
      }
      $html = implode(", ", $html);
      $ctrl["_render"]->displayValue = $html;
      echo newTemplatePattern($ctrl, $html);
    }
    echo "</div>";
    return;
  }
  echo "<div";
  if (isset($meta["destination"])) {
    $cls = ["multi-file-container", "multi-file-container-with-destination"];
    if (in_array("update-ref", $meta["opts"]))
      $cls[] = "multi-file-container-update-ref";
    $meta["destination"] = str_replace(".", "-", $meta["destination"]);

    echo " class=\"".implode(" ", $cls)."\"";
    echo " data-destination=\"".htmlspecialchars($meta["destination"])."\"";
  } else {
    echo " class=\"multi-file-container multi-file-container-without-destination\"";
  }
  echo ">";
  echo "<input class=\"form-control multi-file\" type=\"file\" name=\"".htmlspecialchars($ctrl["name"])."[]\" id=\"".htmlspecialchars($ctrl["id"])."\" multiple";
  if (in_array("dir", $meta["opts"])) {
    echo " webkitdirectory";
  }
  echo "/>";
  echo "</div>";
}

function renderFormItemMoney($meta, $ctrl) {
  echo "<div class=\"input-group\">";
  if (in_array("no-form", $ctrl["render"])) {
    echo "<div class=\"form-control text-right\">";
    $value = "";
    if (isset($ctrl["_values"])) {
      $value = getFormValue($ctrl["name"], $meta["type"], $ctrl["_values"]["_inhalt"], $value);
    }
    echo newTemplatePattern($ctrl, htmlspecialchars($value));
    $ctrl["_render"]->inputValue = $value;
    $ctrl["_render"]->displayValue = htmlspecialchars($value);
    echo "</div>";
  } else {
    echo "<input type=\"text\" class=\"form-control text-right\" value=\"0.00\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\" ".(in_array("required", $meta["opts"]) ? "required=\"required\"": "").">";
  }
  echo "<span class=\"input-group-addon\">".htmlspecialchars($meta["currency"])."</span>";
  echo "</div>";
}

function renderFormItemTextarea($meta, $ctrl) {
  if (in_array("no-form", $ctrl["render"])) {
    echo "<div>";
    $value = "";
    if (isset($ctrl["_values"])) {
      $value = getFormValue($ctrl["name"], $meta["type"], $ctrl["_values"]["_inhalt"], $value);
    }
    echo newTemplatePattern($ctrl, implode("<br/>",explode("\n",htmlspecialchars($value))));
    echo "</div>";
    $ctrl["_render"]->displayValue = htmlspecialchars($value);
    return;
  }

  echo "<textarea class=\"form-control\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  if (isset($meta["min-rows"]))
    echo " rows=".htmlspecialchars($meta["min-rows"]);
  if (in_array("required", $meta["opts"]))
    echo " required=\"required\"";
  echo ">";
  echo "</textarea>";
}

function renderFormItemSelect($meta, $ctrl) {
  global $attributes, $GremiumPrefix;

  if (in_array("no-form", $ctrl["render"])) {
    echo "<div class=\"form-control\">";
    $value = "";
    if (isset($ctrl["_values"])) {
      $value = getFormValue($ctrl["name"], $meta["type"], $ctrl["_values"]["_inhalt"], $value);
    }
    if (isset($meta["data-source"]) && $meta["data-source"] == "own-orgs" && $meta["type"] != "ref") {
      echo newTemplatePattern($ctrl, htmlspecialchars($value));
      $ctrl["_render"]->displayValue = htmlspecialchars($value);
    } else if ($meta["type"] == "ref") {
      $tPattern = "<{ref:".$value."}>";
      echo $tPattern;
      $ctrl["_render"]->templates[$tPattern] = htmlspecialchars("{".$tPattern."}");
      $ctrl["_render"]->postHooks[] = function($ctrl) use ($tPattern, $value) {
        $matches = [];

        if (!preg_match('/^(.*)\[([0-9]+)\]$/', $value, $matches)) {
          $ctrl["_render"]->templates[$tPattern] = htmlspecialchars("miss row idx: ".$value);
          return;
        }

        $currentTable = $matches[1];
        $value = $matches[1];
        $currentRow = (int) $matches[2];
        $txtTr = "[$currentRow] ".$ctrl["_render"]->templates["<{rowTxt:".$currentTable."[".$currentRow."]}>"];
        while (preg_match('/^(.*)\[([0-9]+)\]$/', $value, $matches)) {
          $currentTable = $ctrl["_render"]->parentMap[$currentTable];
          $currentRow = (int) $matches[2];
          $value = $matches[1];
          $txtTr = "[$currentRow] ".$ctrl["_render"]->templates["<{rowTxt:".$currentTable."[".$currentRow."]}>"] . $txtTr;
        }

        $ctrl["_render"]->templates[$tPattern] = $txtTr; // rowTxt is from displayValue and thus already escaped
      };
    }
    echo "</div>";
    return;
  }

  $liveSearch = true;
  if (isset($meta["data-source"]) && $meta["data-source"] == "own-orgs")
    $liveSearch = false;

  $cls = ["select-picker-container"];
  if (in_array("hasFeedback", $meta["opts"]))
    $cls[] = "hasFeedback";
  echo "<div class=\"".implode(" ", $cls)."\">";
  if (in_array("hasFeedback", $meta["opts"]))
    echo '<span class="glyphicon form-control-feedback" aria-hidden="true"></span>';
  echo "<select class=\"selectpicker form-control\" data-live-search=\"".($liveSearch ? "true" : "false")."\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  if (isset($meta["placeholder"]))
    echo " title=\"".htmlspecialchars($meta["placeholder"])."\"";
  if (in_array("multiple", $meta["opts"]))
    echo " multiple";
  if (in_array("required", $meta["opts"]))
    echo " required=\"required\"";
  if ($meta["type"] == "ref") {
    $meta["references"] = str_replace(".", "-", $meta["references"]);
    echo " data-references=\"".htmlspecialchars($meta["references"])."\"";
  }
  echo ">";

  if (isset($meta["data-source"]) && $meta["data-source"] == "own-orgs" && $meta["type"] != "ref") {
    sort($attributes["gremien"]);
    foreach ($GremiumPrefix as $prefix) {
      echo "<optgroup label=\"".htmlspecialchars($prefix)."\">";
      foreach ($attributes["gremien"] as $gremium) {
        if (substr($gremium, 0, strlen($prefix)) != $prefix) continue;
        echo "<option>".htmlspecialchars($gremium)."</option>";
      }
      echo "</optgroup>";
    }
  }
  if ($meta["type"] == "ref")
      echo "<option value=\"\">Bitte auswählen</option>";

  echo "</select>";
  echo "</div>";
}

function renderFormItemDateRange($meta, $ctrl) {
  if (in_array("no-form", $ctrl["render"])) {
    $valueStart = "";
    $valueEnd = "";
    if (isset($ctrl["_values"])) {
      $valueStart = getFormValue($ctrl["name"]."[start]", $meta["type"], $ctrl["_values"]["_inhalt"], $valueStart);
      $valueEnd = getFormValue($ctrl["name"]."[end]", $meta["type"], $ctrl["_values"]["_inhalt"], $valueEnd);
    }
    $tPatternStart = newTemplatePattern($ctrl, htmlspecialchars($valueStart));
    $tPatternEnd =  newTemplatePattern($ctrl, htmlspecialchars($valueEnd));
    echo '<div class="input-daterange input-group">';
    echo '<div class="input-group-addon" style="background-color: transparent; border: none;">von</div>';
    echo "<div class=\"form-control\">{$tPatternStart}</div>";
    echo '<div class="input-group-addon" style="background-color: transparent; border: none;">bis</div>';
    echo "<div class=\"form-control\">{$tPatternEnd}</div>";
    echo "</div>";
    $ctrl["_render"]->displayValue = htmlspecialchars("$valueStart - $valueEnd");
    return;
  }

?>
    <div class="input-daterange input-group"
         data-provide="datepicker"
         data-date-format="yyyy-mm-dd"
         data-date-calendar-weeks="true"
         data-date-language="de"
<?php
  if (in_array("not-before-creation", $meta["opts"])) {
?>
         data-date-start-date="today"
<?php
  }
?>
    >
        <div class="input-group-addon" style="background-color: transparent; border: none;">
          von
        </div>
        <div class="input-group">
          <input type="text" class="input-sm form-control" name="<?php echo htmlspecialchars($ctrl["name"]); ?>[start]" <?php echo (in_array("required", $meta["opts"]) ? "required=\"required\"": ""); ?>/>
          <div class="input-group-addon">
            <span class="glyphicon glyphicon-th"></span>
          </div>
        </div>
        <div class="input-group-addon" style="background-color: transparent; border: none;">
          bis
        </div>
        <div class="input-group">
          <input type="text" class="input-sm form-control" name="<?php echo htmlspecialchars($ctrl["name"]); ?>[end]" <?php echo (in_array("required", $meta["opts"]) ? "required=\"required\"": ""); ?>/>
          <div class="input-group-addon">
            <span class="glyphicon glyphicon-th"></span>
          </div>
        </div>
    </div>
<?php

}


function renderFormItemDate($meta, $ctrl) {
  if (in_array("no-form", $ctrl["render"])) {
    echo "<div class=\"form-control\">";
    $value = "";
    if (isset($ctrl["_values"])) {
      $value = getFormValue($ctrl["name"], $meta["type"], $ctrl["_values"]["_inhalt"], $value);
    }
    echo newTemplatePattern($ctrl, htmlspecialchars($value));
    echo "</div>";
    $ctrl["_render"]->displayValue = htmlspecialchars($value);
    return;
  }

?>
<div class="input-group date"
     data-provide="datepicker"
     data-date-format="yyyy-mm-dd"
     data-date-calendar-weeks="true"
     data-date-language="de"
<?php
  if (in_array("not-before-creation", $meta["opts"])) {
?>
     data-date-start-date="today"
<?php
  }
?>
>
    <input type="text" class="form-control" name="<?php echo htmlspecialchars($ctrl["name"]); ?>" id="<?php echo htmlspecialchars($ctrl["id"]); ?>" <?php echo (in_array("required", $meta["opts"]) ? "required=\"required\"": ""); ?>/>
    <div class="input-group-addon">
        <span class="glyphicon glyphicon-th"></span>
    </div>
</div>
<?php

/*

     [ "id" => "start",       "name" => "Projektbeginn",                      "type" => "date",   "width" => 6,  "opts" => ["not-before-creation"], "not-after" => "field:ende" ],
     [ "id" => "ende",        "name" => "Projektende",                        "type" => "date",   "width" => 6,  "opts" => ["not-before-creation"], "not-before" => "field:start" ],
*/
}

function renderFormItemTable($meta, $ctrl) {
  $withRowNumber = in_array("with-row-number", $meta["opts"]);
  $cls = ["table", "table-striped"];
  $noForm = in_array("no-form", $ctrl["render"]);
  if (!$noForm)
    $cls[] = "dynamic-table";
  if ($noForm) {
    $rowCount = 0;
    if (isset($ctrl["_values"])) {
      $rowCount = (int) getFormValue($ctrl["name"]."[rowCount]", $meta["type"], $ctrl["_values"]["_inhalt"], $rowCount);
    }
  } else {
    $rowCount = 1; // js and php code depends on this!
  }
  if ($rowCount == 0) return false; //empty table
?>

  <table class="<?php echo implode(" ", $cls); ?>" id="<?php echo htmlspecialchars($ctrl["id"]); ?>" name="<?php echo htmlspecialchars($ctrl["name"]); ?>">

<?php
  echo "<input type=\"hidden\" value=\"0\" name=\"".htmlspecialchars($ctrl["name"])."[rowCount]\" class=\"store-row-count\"/>";

  if (in_array("with-headline", $meta["opts"])) {

?>

    <thead>
      <tr>
<?php
        echo "<th></th>";
        if ($withRowNumber) {
          echo "<th></th>";
        }
        foreach ($meta["columns"] as $col) {
          echo "<th>".htmlspecialchars($col["name"])."</th>";
        }
?>
      </tr>
    </thead>

<?php
  }

?>
    <tbody>
<?php
     $columnSums = [];
     for ($rowNumber = 0; $rowNumber < $rowCount; $rowNumber++) {
       $cls = ["dynamic-table-row"];
       if (!$noForm)
         $cls[] = "new-table-row";
       $newSuffix = $ctrl["suffix"];
       if (!$noForm)
         $newSuffix[] = false;
       else
         $newSuffix[] = $rowNumber;
       $ctrl["_render"]->inputValue = false;
       $ctrl["_render"]->displayValue = false;
       $rowTxt = [];
?>
       <tr class="<?php echo implode(" ", $cls); ?>">
<?php
        if ($withRowNumber) {
          echo "<td class=\"row-number\">".($rowNumber+1)."</td>";
        }
        echo "<td class=\"delete-row\">";
        if (!$noForm)
          echo "<a href=\"\" class=\"delete-row\"><i class=\"fa fa-fw fa-trash\"></i></a>";
        echo "</td>";

        foreach ($meta["columns"] as $i => $col) {
          if (!isset($col["opts"]))
            $col["opts"] = [];

          if (in_array("title", $col["opts"]))
            $clsTitle = "dynamic-table-column-title";
          else
            $clsTitle = "dynamic-table-column-no-title";

          $newCtrl = ["wrapper"=> "td", "suffix" => $newSuffix, "class" => [ "{$ctrl["id"]}-col-$i", $clsTitle ] ];
          if ($noForm)
            $ctrl["_render"]->displayValue = false;

          renderFormItem($col, array_merge($ctrl, $newCtrl));

          if (in_array("title", $col["opts"]))
            $rowTxt[] = $ctrl["_render"]->displayValue;

          if (in_array("sum-over-table-bottom", $col["opts"])) {
            if (!isset($columnSums[$i]))
              $columnSums[$i] = floatval("0");
            if ($noForm && $ctrl["_render"]->inputValue !== false)
              $columnSums[$i] += floatval($ctrl["_render"]->inputValue);
          }
        }

        $refname = getFormName($ctrl["name"]);
        $ctrl["_render"]->templates["<{rowTxt:".$refname."[".$rowNumber."]}>"] = implode(", ", $rowTxt);

?>
       </tr>
<?php
     }
?>
    </tbody>
<?php
    if (count($columnSums) > 0) {
?>
    <tfoot>
      <tr>
<?php
        if ($withRowNumber) {
          echo "<th></th>";
        }
?>
        <th></th>
<?php
        foreach ($meta["columns"] as $i => $col) {
          if (!isset($col["opts"])) $col["opts"] = [];
?>
        <th>
<?php
          if (in_array("sum-over-table-bottom", $col["opts"])) {
            $value = $columnSums[$i];
            $value = number_format($value, 2, ".", "");
            echo "<div class=\"input-group\">";
            echo "<span class=\"input-group-addon\">Σ</span>";
            echo "<div class=\"column-sum text-right form-control\" data-col-id=\"{$ctrl["id"]}-col-$i\">$value</div>";
            echo "<span class=\"input-group-addon\">".htmlspecialchars($col["currency"])."</span>";
            echo "</div>";
          }
?>
        </th>
<?php
        }
?>
      </tr>
    </tfoot>
<?php
    } /* has column sums */

    $ctrl["_render"]->inputValue = false;
    $ctrl["_render"]->displayValue = false;
?>
  </table>
<?

}
