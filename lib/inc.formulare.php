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

function getFormValue($name, $type, $values, $defaultValue = false) {
  $matches = [];
  if (preg_match("/^formdata\[([^\]]*)\](.*)/", $name, $matches)) {
    $name = $matches[1].$matches[2];
  } else {
    return $defaultValue;
  }

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

function getFormFile($name, $values, $defaultValue = false) {
  $matches = [];
  if (preg_match("/^formdata\[([^\]]*)\](.*)/", $name, $matches)) {
    $name = $matches[1].$matches[2];
  } else {
    return $defaultValue;
  }

  foreach($values as $row) {
    if ($row["fieldname"] != $name)
      continue;
    return $row;
  }
  return $defaultValue;
}

function renderForm($meta, $ctrl = false) {

  foreach ($meta as $item) {
    renderFormItem($item, $ctrl);
  }

}

function renderFormItem($meta,$ctrl = false) {

  if (!isset($meta["id"])) {
    echo "Missing \"id\" in ";
    print_r($meta);
    die();
  }

  if (!isset($meta["opts"]))
   $meta["opts"] = [];

  if ($ctrl === false) $ctrl = [];
  if (!isset($ctrl["wrapper"])) {
    $wrapper = "div";
  } else {
    $wrapper = $ctrl["wrapper"];
    unset($ctrl["wrapper"]);
  }
  if (!isset($ctrl["render"]))
   $ctrl["render"] = [];

  if (isset($ctrl["class"]))
    $classes = $ctrl["class"];
  else
    $classes = [];

  if (isset($meta["width"]))
    $classes[] = "col-xs-{$meta["width"]}";

  echo "<$wrapper class=\"".implode(" ", $classes)."\">";

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

  echo "<div class=\"".join(" ", $cls)."\">";
  echo "<input type=\"hidden\" value=\"{$meta["type"]}\" name=\"formtype[".htmlspecialchars($meta["id"])."]\"/>";

  if (isset($meta["title"]) && isset($meta["id"]))
    echo "<label class=\"control-label\" for=\"{$ctrl["id"]}\">".htmlspecialchars($meta["title"])."</label>";
  elseif (isset($meta["title"]))
    echo "<label class=\"control-label\">".htmlspecialchars($meta["title"])."</label>";

  switch ($meta["type"]) {
    case "h1":
    case "h2":
    case "h3":
    case "h4":
    case "h5":
    case "h6":
    case "plaintext":
      renderFormItemPlainText($meta,$ctrl);
      break;
    case "group":
      renderFormItemGroup($meta,$ctrl);
      break;
    case "text":
    case "email":
    case "url":
      renderFormItemText($meta,$ctrl);
      break;
    case "money":
      renderFormItemMoney($meta,$ctrl);
      break;
    case "textarea":
      renderFormItemTextarea($meta,$ctrl);
      break;
    case "select":
    case "ref":
      renderFormItemSelect($meta,$ctrl);
      break;
    case "date":
      renderFormItemDate($meta,$ctrl);
      break;
    case "daterange":
      renderFormItemDateRange($meta,$ctrl);
      break;
    case "table":
      renderFormItemTable($meta,$ctrl);
      break;
    case "file":
      renderFormItemFile($meta,$ctrl);
      break;
    case "multifile":
      renderFormItemMultiFile($meta,$ctrl);
      break;
    default:
      echo "<pre>"; print_r($meta); echo "</pre>";
      die("Unkown form element meta type: ".$meta["type"]);
  }

  echo '<div class="help-block with-errors"></div>';
  echo "</div>";

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
  echo "<${elem}>{$value}</${elem}>";
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
    if ($meta["type"] == "email" && !empty($value))
      echo "<a href=\"mailto:".htmlspecialchars($value)."\">";
    if ($meta["type"] == "url" && !empty($value))
      echo "<a href=\"".htmlspecialchars($value)."\" target=\"_blank\">";
    echo htmlspecialchars($value);
    if ($meta["type"] == "email" && !empty($value))
      echo "</a>";
    if ($meta["type"] == "url" && !empty($value))
      echo "</a>";
    echo "</div>";
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

    echo " value=\"".htmlspecialchars($value)."\"";
  }
  echo "/>";
  if (in_array("hasFeedback", $meta["opts"]))
    echo '<span class="glyphicon form-control-feedback" aria-hidden="true"></span>';
}

function renderFormItemFile($meta, $ctrl) {
  if (in_array("no-form", $ctrl["render"])) {
    echo "<div class=\"form-control\">";
    $file = false;
    if (isset($ctrl["_values"])) {
      $file = getFormFile($ctrl["name"], $ctrl["_values"]["_anhang"], $file);
    }
    echo htmlspecialchars($file["filename"]);
    echo "</div>";
    return;
  }
  echo "<div class=\"single-file-container\">";
  echo "<input class=\"form-control single-file\" type=\"file\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"/>";
  echo "</div>";
}

function renderFormItemMultiFile($meta, $ctrl) {
// FIXME multi-value
  if (in_array("no-form", $ctrl["render"])) {
    echo "<div class=\"form-control\">";
    $file = false;
    if (isset($ctrl["_values"])) {
      $file = getFormFile($ctrl["name"], $ctrl["_values"]["_anhang"], $file);
    }
    echo htmlspecialchars($file["filename"]);
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
    echo htmlspecialchars($value);
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
    echo implode("<br/>",explode("\n",htmlspecialchars($value)));
    echo "</div>";
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
      echo htmlspecialchars($value);
    }
    if ($meta["type"] == "ref") {
      echo "FIXME: ".htmlspecialchars($value);
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
    echo '<div class="input-daterange input-group">';
    echo '<div class="input-group-addon" style="background-color: transparent; border: none;">von</div>';
    echo "<div class=\"form-control\">".htmlspecialchars($valueStart)."</div>";
    echo '<div class="input-group-addon" style="background-color: transparent; border: none;">bis</div>';
    echo "<div class=\"form-control\">".htmlspecialchars($valueEnd)."</div>";
    echo "</div>";
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
    echo htmlspecialchars($value);
    echo "</div>";
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
?>
  <table class="<?php echo implode(" ", $cls); ?>" id="<?php echo htmlspecialchars($ctrl["id"]); ?>" name="<?php echo htmlspecialchars($ctrl["id"]); ?>">
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
     for ($rowNumber = 0; $rowNumber < $rowCount; $rowNumber++) {
       $cls = ["dynamic-table-row"];
       if (!$noForm)
         $cls[] = "new-table-row";
       $newSuffix = $ctrl["suffix"];
       if (!$noForm)
         $newSuffix[] = false;
       else
         $newSuffix[] = $rowNumber;
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
          if (isset($col["opts"]) && in_array("title", $col["opts"]))
            $clsTitle = "dynamic-table-column-title";
          else
            $clsTitle = "dynamic-table-column-no-title";
          renderFormItem($col,array_merge($ctrl, ["wrapper"=> "td", "suffix" => $newSuffix, "class" => [ "{$ctrl["id"]}-col-$i", $clsTitle ] ]));
        }
?>
       </tr>
<?php
     }
?>
    </tbody>
<?php
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
            echo "<div class=\"input-group\">";
            echo "<span class=\"input-group-addon\">Σ</span>";
            echo "<div class=\"column-sum text-right form-control\" data-col-id=\"{$ctrl["id"]}-col-$i\">You should not see this o.O</div>";
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
  </table>
<?

}
