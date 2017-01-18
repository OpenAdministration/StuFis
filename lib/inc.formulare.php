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

function renderForm($meta) {

  foreach ($meta as $item) {
    renderFormItem($item);
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

  if (isset($ctrl["class"]))
    $classes = $ctrl["class"];
  else
    $classes = [];

  if (isset($meta["width"]))
    $classes[] = "col-xs-{$meta["width"]}";

  echo "<$wrapper class=\"".implode(" ", $classes)."\">";

  $ctrl["id"] = $meta["id"];
  $ctrl["name"] = "formdata[{$meta["id"]}]";
  if (isset($ctrl["suffix"])) {
    $ctrl["name"] .= "[]";
  }
  if (isset($ctrl["suffix"]) && $ctrl["suffix"]) {
    $ctrl["id"] = $meta["id"]."-".$ctrl["suffix"];
  }
  $ctrl["id"] = str_replace(".", "-", $ctrl["id"]);

  echo "<div class=\"form-group\">";
  echo "<input type=\"hidden\" value=\"{$meta["type"]}\" name=\"formtype[".htmlspecialchars($meta["id"])."]\"/>";

  if (isset($meta["title"]) && isset($meta["id"]))
    echo "<label class=\"control-label\" for=\"{$ctrl["id"]}\">".htmlspecialchars($meta["title"])."</label>";
  elseif (isset($meta["title"]))
    echo "<label class=\"control-label\">".htmlspecialchars($meta["title"])."</label>";

  switch ($meta["type"]) {
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

  echo "</div>";

  if (isset($meta["width"]))
    echo "</$wrapper>";
  else
    echo "</$wrapper>";

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

  echo "<input class=\"form-control\" type=\"{$meta["type"]}\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  if (isset($meta["placeholder"]))
    echo " placeholder=\"".htmlspecialchars($meta["placeholder"])."\"";
  if (in_array("required", $meta["opts"]))
    echo " required=\"required\"";
  if (isset($meta["prefill"])) {
    $value = "";
    if ($meta["prefill"] == "user:mail")
      $value = getUserMail();

    echo " value=\"".htmlspecialchars($value)."\"";
  }
  echo "/>";
}

function renderFormItemFile($meta, $ctrl) {
  echo "<div class=\"single-file-container\">";
  echo "<input class=\"form-control single-file\" type=\"file\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"/>";
  echo "</div>";
}

function renderFormItemMultiFile($meta, $ctrl) {
/*
  5 1. Upload Area für mehrere Dateien (auch Ordner)
  6 2. Die dann andere File-Felder (Tabelle) ersetzt (dort sind weiterhin File-Felder erlaubt und drinnen, damit man dort zusätzlich hochladen kann)
  7 D.h. es braucht immer eine Tabelle.
  8 3. Ggf. aktualisierung von ref-Feldern bezogen auf diese Tabelle
  9 4. Vodoo für Cloned-Upload-Felder
 10 5. AjaxUpload
 11 6. Folder Option
*/
  echo "<div";
  if (isset($meta["destination"])) {
    echo " class=\"multi-file-container multi-file-container-with-destination\"";
    $meta["destination"] = str_replace(".", "-", $meta["destination"]);
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
  echo "<input type=\"text\" class=\"form-control text-right\" value=\"0.00\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\" ".(in_array("required", $meta["opts"]) ? "required=\"required\"": "").">";
  echo "<span class=\"input-group-addon\">".htmlspecialchars($meta["currency"])."</span>";
  echo "</div>";
}

function renderFormItemTextarea($meta, $ctrl) {
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
  echo "<div class=\"select-picker-container\">";
  echo "<select class=\"selectpicker form-control\" data-live-search=\"true\" name=\"".htmlspecialchars($ctrl["name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
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

  if (isset($meta["data-source"]) && $meta["data-source"] == "own-orgs") {
    sort($attributes["gremien"]);
    foreach ($attributes["gremien"] as $gremium) {
      $found = (count($GremiumPrefix) == 0);
      foreach ($GremiumPrefix as $prefix)
        $found |= (substr($gremium, 0, strlen($prefix)) == $prefix);
      if (!$found) continue;

      echo "<option>".htmlspecialchars($gremium)."</option>";
    }
  }
  if ($meta["type"] == "ref")
      echo "<option value=\"\">Bitte auswählen</option>";

  echo "</select>";
  echo "</div>";
}

function renderFormItemDateRange($meta, $ctrl) {
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
        <input type="text" class="input-sm form-control" name="<?php echo htmlspecialchars($ctrl["name"]); ?>[start]" <?php echo (in_array("required", $meta["opts"]) ? "required=\"required\"": ""); ?>/>
        <div class="input-group-addon" style="border-top-right-radius: 4px; border-bottom-right-radius: 4px;">
          <span class="glyphicon glyphicon-th"></span>
        </div>
        <div class="input-group-addon" style="background-color: transparent; border: none;">
          bis
        </div>
        <input type="text" class="input-sm form-control" name="<?php echo htmlspecialchars($ctrl["name"]); ?>[end]" style="border-top-left-radius: 4px; border-bottom-left-radius: 4px;" <?php echo (in_array("required", $meta["opts"]) ? "required=\"required\"": ""); ?>/>
        <div class="input-group-addon">
          <span class="glyphicon glyphicon-th"></span>
        </div>
    </div>
<?php

}


function renderFormItemDate($meta, $ctrl) {

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

?>
  <table class="table table-striped dynamic-table" id="<?php echo htmlspecialchars($ctrl["id"]); ?>" name="<?php echo htmlspecialchars($ctrl["id"]); ?>">
<?php

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
       <tr class="new-table-row dynamic-table-row">
<?php
        if ($withRowNumber) {
          echo "<td class=\"row-number\">1.</td>";
        }
        echo "<td class=\"delete-row\">";
        echo "<a href=\"\" class=\"delete-row\"><i class=\"fa fa-fw fa-trash\"></i></a>";
        echo "</td>";

        foreach ($meta["columns"] as $i => $col) {
          if (isset($col["opts"]) && in_array("title", $col["opts"]))
            $clsTitle = "dynamic-table-column-title";
          else
            $clsTitle = "dynamic-table-column-no-title";
          renderFormItem($col,array_merge($ctrl, ["wrapper"=> "td", "suffix" => false, "class" => [ "{$ctrl["id"]}-col-$i", $clsTitle ] ]));
        }
?>
       </tr>
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
