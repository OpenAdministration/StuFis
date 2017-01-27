<?php

loadForms();

function registerForm( $type, $revision, $layout, $config ) {
  global $formulare;

  if (!isset($formulare[$type])) die("missing form-class $type");
  if (isset($formulare[$type][$revision])) die("duplicate form-id $type:$revision");
  $formulare[$type][$revision] = ["layout" => $layout, "config" => $config, "type" => $type, "revision" => $revision];
}

function registerFormClass( $type, $config ) {
  global $formulare;

  if (isset($formulare[$type])) die("duplicate form-class $type");
  $formulare[$type] = [];
  $formulare[$type]["_class"] = $config;
}

function getFormClass( $type ) {
  global $formulare;

  if (!isset($formulare[$type])) die("unknown form-class $type");

  return $formulare[$type]["_class"];
}

function loadForms() {
  global $formulare;

  $handle = opendir(SYSBASE."/config/formulare");

  $files = [];
  while (false !== ($entry = readdir($handle))) {
    if (substr($entry, -4) !== ".php") continue;
    $files[] = $entry;
  }

  function cmp($ax, $bx)
  {
    $a = strlen($ax);
    $b = strlen($bx);

    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
  }

  $a = array(3, 2, 5, 6, 1);

  usort($files, "cmp");

  foreach ($files as $entry) {
    require SYSBASE."/config/formulare/".$entry;
  }

  closedir($handle);

}

function getForm($type, $revision) {
  global $formulare;

  if (!isset($formulare[$type])) return false;
  if (!isset($formulare[$type][$revision])) return false;

  $form = $formulare[$type][$revision];
  $form["_class"] = $formulare[$type]["_class"];

  return $form;
}

function getFormLayout($type, $revision) {
  global $formulare;

  if (!isset($formulare[$type])) return false;
  if (!isset($formulare[$type][$revision])) return false;

  return $formulare[$type][$revision]["layout"];
}

function getFormConfig($type, $revision) {
  global $formulare;

  if (!isset($formulare[$type])) return false;
  if (!isset($formulare[$type][$revision])) return false;

  return $formulare[$type][$revision]["config"];
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
  return getFormValueInt($name, $type, $values, $defaultValue);
}

function getFormValueInt($name, $type, $values, $defaultValue = false) {
  foreach($values as $row) {
    if ($row["fieldname"] != $name)
      continue;
    if ($type !== null && $row["contenttype"] != $type) {
      add_message("Feld $name: erwarteter Typ = \"$type\", erhaltener Typ = \"{$row["contenttype"]}\"");
      continue;
    }
    return $row["value"];
  }
  return $defaultValue;
}

function getFormEntry($name, $type, $values) {
  foreach($values as $row) {
    if ($row["fieldname"] != $name)
      continue;
    if ($type !== null && $row["contenttype"] != $type) {
      add_message("Feld $name: erwarteter Typ = \"$type\", erhaltener Typ = \"{$row["contenttype"]}\"");
      continue;
    }
    return $row;
  }
  return false;
}

function getFormEntries($name, $type, $values) {
  $ret = [];
  foreach($values as $row) {
    if ($row["fieldname"] != $name && (substr($row["fieldname"], 0, strlen($name."[")) != $name."["))
      continue;
    if ($type !== null && $row["contenttype"] != $type) {
      add_message("Feld $name: erwarteter Typ = \"$type\", erhaltener Typ = \"{$row["contenttype"]}\"");
      continue;
    }
    $ret[] = $row;
  }
  return $ret;
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
    if ($row["fieldname"] != $name && (substr($row["fieldname"], 0, strlen($name."[")) != $name."["))
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

function renderForm($form, $ctrl = false) {

  if (!isset($form["layout"]))
    die("renderForm: \$form has no layout");

  $layout = $form["layout"];

  if (!is_array($ctrl))
    $ctrl = [];

  if (isset($form["_class"]))
    $ctrl["_class"] = $form["_class"];
  if (isset($form["config"]))
    $ctrl["_config"] = $form["config"];

  $ctrl["_render"] = new stdClass();
  $ctrl["_render"]->displayValue = false;
  $ctrl["_render"]->templates = [];
  $ctrl["_render"]->parentMap = []; /* map currentName => parentName */
  $ctrl["_render"]->currentParent = false;
  $ctrl["_render"]->currentParentRow = false;
  $ctrl["_render"]->postHooks = []; /* e.g. ref-field */
  $ctrl["_render"]->addToSumMeta = [];
  $ctrl["_render"]->addToSumValue = [];
  $ctrl["_render"]->addToSumValueByRowRecursive = [];
  $ctrl["_render"]->referencedBy = []; /* tableRowReferenced -> tableRowWhereReferenceIs */

  if (!isset($ctrl["render"]))
    $ctrl["render"] = [];

  ob_start();
  foreach ($layout as $item) {
    renderFormItem($item, $ctrl);
  }
  $txt = ob_get_contents();
  ob_end_clean();

  foreach($ctrl["_render"]->postHooks as $hook) {
    $hook($ctrl);
  }

  $txt = processTemplates($txt, $ctrl);

  echo $txt;
}


function processTemplates($txt, $ctrl) {
  return str_replace(array_keys($ctrl["_render"]->templates), array_values($ctrl["_render"]->templates), $txt);
}

function renderFormItem($layout,$ctrl = false) {

  if (!isset($layout["id"])) {
    echo "Missing \"id\" in ";
    print_r($layout);
    die();
  }

  if (!isset($layout["opts"]))
   $layout["opts"] = [];

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

  if (isset($layout["width"]))
    $classes[] = "col-xs-{$layout["width"]}";

  $ctrl["id"] = $layout["id"];
  $ctrl["name"] = "formdata[{$layout["id"]}]";
  $ctrl["orig-name"] = $ctrl["name"];

  if (!isset($ctrl["suffix"]))
   $ctrl["suffix"] = [];
  foreach($ctrl["suffix"] as $suffix) {
    $ctrl["name"] .= "[{$suffix}]";
    $ctrl["orig-name"] .= "[]";
    if ($suffix !== false) {
      $ctrl["id"] .= "-".$suffix;
    }
  }
  $ctrl["id"] = str_replace(".", "-", $ctrl["id"]);

  $cls = ["form-group"];
  if (in_array("hasFeedback", $layout["opts"])) $cls[] = "has-feedback";

  $noForm = in_array("no-form", $ctrl["render"]);
  $noFormMarkup = in_array("no-form-markup", $ctrl["render"]);

  ob_start();
  switch ($layout["type"]) {
    case "h1":
    case "h2":
    case "h3":
    case "h4":
    case "h5":
    case "h6":
    case "plaintext":
      $isEmpty = renderFormItemPlainText($layout,$ctrl);
      break;
    case "group":
      $isEmpty = renderFormItemGroup($layout,$ctrl);
      break;
    case "text":
    case "email":
    case "url":
      $isEmpty = renderFormItemText($layout,$ctrl);
      break;
    case "money":
      $isEmpty = renderFormItemMoney($layout,$ctrl);
      break;
    case "textarea":
      $isEmpty = renderFormItemTextarea($layout,$ctrl);
      break;
    case "select":
    case "ref":
      $isEmpty = renderFormItemSelect($layout,$ctrl);
      break;
    case "date":
      $isEmpty = renderFormItemDate($layout,$ctrl);
      break;
    case "daterange":
      $isEmpty = renderFormItemDateRange($layout,$ctrl);
      break;
    case "table":
      $isEmpty = renderFormItemTable($layout,$ctrl);
      break;
    case "file":
      $isEmpty = renderFormItemFile($layout,$ctrl);
      break;
    case "multifile":
      $isEmpty = renderFormItemMultiFile($layout,$ctrl);
      break;
    case "invref":
      $isEmpty = renderFormItemInvRef($layout,$ctrl);
      break;
    default:
      ob_end_flush();
      echo "<pre>"; print_r($layout); echo "</pre>";
      die("Unkown form element meta type: ".$layout["type"]);
  }
  $txt = ob_get_contents();
  ob_end_clean();

  if (!$noFormMarkup) {
    echo "<$wrapper class=\"".implode(" ", $classes)."\" data-formItemType=\"".htmlspecialchars($layout["type"])."\"";
    echo " style=\"";
    if (isset($layout["max-width"]))
      echo "max-width: {$layout["max-width"]};";
    if (isset($layout["min-width"]))
      echo "min-width: {$layout["min-width"]};";
    echo "\"";
    echo ">";
  }

  if ($isEmpty !== false) {
    if (!$noFormMarkup)
      echo "<div class=\"".join(" ", $cls)."\">";
    if (!$noForm)
      echo "<input type=\"hidden\" value=\"{$layout["type"]}\" name=\"formtype[".htmlspecialchars($layout["id"])."]\"/>";

    if (isset($layout["title"]) && isset($layout["id"]))
      echo "<label class=\"control-label\" for=\"{$ctrl["id"]}\">".htmlspecialchars($layout["title"])."</label>";
    elseif (isset($layout["title"]))
      echo "<label class=\"control-label\">".htmlspecialchars($layout["title"])."</label>";

    echo $txt;

    if (!in_array("no-form", $ctrl["render"]))
      echo '<div class="help-block with-errors"></div>';
    if (!$noFormMarkup)
      echo "</div>";
  }

  if (!$noFormMarkup) {
    if (isset($layout["width"]))
      echo "</$wrapper>";
    else
      echo "</$wrapper>";
  }

}

function renderFormItemPlainText($layout, $ctrl) {
  $value = "";
  if (isset($layout["value"]))
    $value = $layout["value"];
  if (isset($layout["autoValue"])) {
    if (substr($layout["autoValue"],0,6) == "class:") {
      $field = substr($layout["autoValue"], 6);
      if (isset($ctrl["_class"]) && $ctrl["_class"][$field])
        $value = $ctrl["_class"][$field];
    }
  }
  $value = htmlspecialchars($value);
  $value = implode("<br/>", explode("\n", $value));
  switch ($layout["type"]) {
    case "h1":
    case "h2":
    case "h3":
    case "h4":
    case "h5":
    case "h6":
      $elem = $layout["type"];
      break;
    default:
      $elem = "div";
  }
  $tPattern = newTemplatePattern($ctrl, $value);
  echo "<${elem}>{$tPattern}</${elem}>";
}

function renderFormItemGroup($layout, $ctrl) {
  if (in_array("well", $layout["opts"]))
     echo "<div class=\"well\">";

  foreach ($layout["children"] as $child) {
    renderFormItem($child, $ctrl);
  }
  if (in_array("well", $layout["opts"]))
    echo "<div class=\"clearfix\"></div></div>";
}

function renderFormItemText($layout, $ctrl) {
  global $nonce, $URIBASE, $attributes, $GremiumPrefix;

  $noForm = in_array("no-form", $ctrl["render"]);
  $noFormMarkup = in_array("no-form-markup", $ctrl["render"]);
  $isWikiUrl = ($layout["type"] == "url" && in_array("wikiUrl", $layout["opts"]));
  $isDS = isset($layout["data-source"]);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  } elseif (isset($layout["value"])) {
    $value = $layout["value"];
  } elseif (!$noForm && isset($layout["prefill"]) && $layout["prefill"] == "user:mail") {
    $value = getUserMail();
  }
  $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));

  $ctrl["_render"]->displayValue = htmlspecialchars($value);
  if (isset($layout["addToSum"])) {
    foreach ($layout["addToSum"] as $addToSumId) {
      $ctrl["_render"]->addToSumMeta[$addToSumId] = $layout;
      if (!isset($ctrl["_render"]->addToSumValue[$addToSumId]))
        $ctrl["_render"]->addToSumValue[$addToSumId] = 0.00;
      $ctrl["_render"]->addToSumValue[$addToSumId] += (float) $value;
    }
  }

  if (!$noFormMarkup && $noForm) {
    echo "<div class=\"form-control\"";
  } elseif (!$noForm) {
    if ($isWikiUrl || $isDS) {
      $cls = ["input-group"];
      if ($isDS)
        $cls[] = "custom-combobox";
      echo "<div class=\"".htmlspecialchars(implode(" ",$cls))."\">";
    }
    echo "<input class=\"form-control\" type=\"{$layout["type"]}\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  }

  if (isset($layout["addToSum"])) { # filter based on [data-addToSum~={$addToSumId}]
    echo " data-addToSum=\"".htmlspecialchars(implode(" ", $layout["addToSum"]))."\"";
  }
  if (isset($layout["printSum"])) { # filter based on [data-printSum~={$printSumId}]
    echo " data-printSum=\"".htmlspecialchars(implode(" ", $layout["printSum"]))."\"";
  }

  if ($noForm) {
    if (!$noFormMarkup) {
      echo ">";
    }
    if ($layout["type"] == "email" && !empty($value))
      echo "<a href=\"mailto:{$tPattern}\" class=\"link-shows-url\">";
    if ($layout["type"] == "url" && !empty($value))
      echo "<a href=\"{$tPattern}\" target=\"_blank\" class=\"link-shows-url\">";
    echo $tPattern;
    if ($layout["type"] == "email" && !empty($value))
      echo "</a>";
    if ($layout["type"] == "url" && !empty($value))
      echo "</a>";
    if (!$noFormMarkup) {
      echo "</div>";
    }
  } else {
    if (isset($layout["placeholder"]))
      echo " placeholder=\"".htmlspecialchars($layout["placeholder"])."\"";
    if (in_array("required", $layout["opts"]))
      echo " required=\"required\"";
    if (isset($layout["minLength"]))
      echo " data-minlength=\"".htmlspecialchars($layout["minLength"])."\"";
    if (isset($layout["maxLength"]))
      echo " maxlength=\"".htmlspecialchars($layout["maxLength"])."\"";
    if (isset($layout["pattern"]))
      echo " pattern=\"".htmlspecialchars($layout["pattern"])."\"";
    if (isset($layout["pattern-error"]))
      echo " data-pattern-error=\"".htmlspecialchars($layout["pattern-error"])."\"";
    if ($layout["type"] == "email") {
      echo " data-remote=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=validate.email&nonce=".urlencode($nonce))."\"";
      echo " data-remote-error=\"Ungültige eMail-Adresse\"";
    } elseif ($layout["type"] == "url" && in_array("wikiUrl", $layout["opts"])) {
      echo " data-tree-url=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=propose.wiki&nonce=".urlencode($nonce))."\"";
      echo " data-remote=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=validate.wiki&nonce=".urlencode($nonce))."\"";
    }
    echo " value=\"{$tPattern}\"";
    echo "/>";
    if ($isWikiUrl) {
      echo "<div class=\"input-group-btn dropdown-toggle\">";
      echo "<span></span>"; // for borders
      echo "<button class=\"btn btn-default tree-view-btn ".(in_array("hasFeedback", $layout["opts"]) ? "form-control":"")." dropdown-toggle tree-view-toggle\">";
      echo "<span class=\"caret mycaret-down tree-view-show\"></span>";
      echo "<i class=\"fa fa-spinner fa-spin tree-view-spinning\" style=\"font-size:20px\"></i>";
      echo "<span class=\"caret mycaret-up tree-view-hide\"></span>";
      echo "</button>";
      echo "</div>";
    }
    if ($isDS) {
      $dsId = $ctrl["id"]."-dataSource";
?>
     <ul id="<?php echo htmlspecialchars($dsId); ?>" class="dropdown-menu dropdown-menu-rightX" role="menu">
<?php
       if ($layout["data-source"] == "own-orgs") {
         $gremien = $attributes["gremien"];
         if ($value != "" && !in_array($value, $attributes["gremien"]))
           $gremien[] = $value;
         sort($gremien, SORT_STRING | SORT_FLAG_CASE);
         $lastNotEmpty = false;
         foreach ($GremiumPrefix as $prefix) {
           $thisNotEmpty = false;
           foreach ($gremien as $gremium) {
             if (substr($gremium, 0, strlen($prefix)) != $prefix) continue;
             if ($lastNotEmpty) echo '<li role="separator" class="divider"></li>'; $lastNotEmpty = false;
             if (!$thisNotEmpty) echo '<li class="dropdown-header"><span class="text">'.$prefix.'</span></li>'; $thisNotEmpty = true;
             echo '<li><a class="opt" role="option" aria-disabled="false" aria-selected="false" value="';
             echo htmlspecialchars($gremium);
             echo '"><span class="text">';
             echo htmlspecialchars($gremium);
             echo '</span></a></li>';
           }
           $lastNotEmpty |= $thisNotEmpty;
         }
       }
       if ($layout["data-source"] == "own-mailinglists") {
         $mailinglists = $attributes["mailinglists"];
         if ($value != "" && !in_array($value, $attributes["mailinglists"]))
           $mailinglists[] = $value;
         sort($mailinglists, SORT_STRING | SORT_FLAG_CASE);
         foreach ($mailinglists as $mailinglist) {
           echo "<li class=\"input-xs\"><a href=\"#\" value=\"".htmlspecialchars($mailinglist)."\">";
           echo htmlspecialchars($mailinglist);
           echo "</a></li>";
         }
       }
?>
     </ul>
   <div class="input-group-btn custom-combobox dropdown-toggle" data-toggle="dropdown">
     <button type="button" class="btn btn-default dropdown-toggle <?php if (in_array("hasFeedback", $layout["opts"])) echo "form-control"; ?>">
       <span class="caret"></span>
     </button>
   </div>
<?php
    }
    if ($isWikiUrl || $isDS)
      echo "</div>"; // input-group
    if (in_array("hasFeedback", $layout["opts"]))
      echo '<span class="glyphicon form-control-feedback" aria-hidden="true"></span>';
    if ($layout["type"] == "url" && in_array("wikiUrl", $layout["opts"])) {
      echo '<div class="tree-view" aria-hidden="true" id="'.htmlspecialchars($ctrl["id"]).'-treeview"></div>';
    }
  }
}

function renderFormItemMoney($layout, $ctrl) {
  $noForm = in_array("no-form", $ctrl["render"]);
  $noFormMarkup = in_array("no-form-markup", $ctrl["render"]);

  $value = "0.00";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  } elseif (isset($layout["value"])) {
    $value = $layout["value"];
  }
  $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));

  $ctrl["_render"]->displayValue = htmlspecialchars($value);
  if (isset($layout["addToSum"])) {
    foreach ($layout["addToSum"] as $addToSumId) {
      $ctrl["_render"]->addToSumMeta[$addToSumId] = $layout;
      if (!isset($ctrl["_render"]->addToSumValue[$addToSumId]))
        $ctrl["_render"]->addToSumValue[$addToSumId] = 0.00;
      $ctrl["_render"]->addToSumValue[$addToSumId] += (float) $value;
    }
  }

  echo "<div class=\"input-group\">";

  if (in_array("is-sum", $layout["opts"]))
    echo "<span class=\"input-group-addon\">Σ</span>";

  if ($noForm && $noFormMarkup) {
    echo "<div class=\"text-right visible-inline\"";
  } else if ($noForm && !$noFormMarkup) {
    echo "<div class=\"form-control text-right\"";
  } else {
    echo "<input type=\"text\" class=\"form-control text-right\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  }

  if (isset($layout["addToSum"])) { # filter based on [data-addToSum~={$addToSumId}]
    echo " data-addToSum=\"".htmlspecialchars(implode(" ", $layout["addToSum"]))."\"";
  }
  if (isset($layout["printSum"])) { # filter based on [data-printSum~={$printSumId}]
    echo " data-printSum=\"".htmlspecialchars(implode(" ", $layout["printSum"]))."\"";
  }

  if ($noForm) {
    echo ">";
    echo $tPattern;
    echo "</div>";
  } else {
    if (in_array("required", $layout["opts"]))
      echo " required=\"required\"";
    echo " value=\"{$tPattern}\"";
    echo "/>";
  }

  echo "<span class=\"input-group-addon\">".htmlspecialchars($layout["currency"])."</span>";
  echo "</div>";
}

function renderFormItemTextarea($layout, $ctrl) {
  $noForm = in_array("no-form", $ctrl["render"]);
  $noFormMarkup = in_array("no-form-markup", $ctrl["render"]);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  } elseif (isset($layout["value"])) {
    $value = $layout["value"];
  }

  $ctrl["_render"]->displayValue = htmlspecialchars($value);

  if ($noForm) {
    echo "<div>";
    echo newTemplatePattern($ctrl, implode("<br/>",explode("\n",htmlspecialchars($value))));
    echo "</div>";
  } else {
    echo "<textarea class=\"form-control\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
    if (isset($layout["min-rows"]))
      echo " rows=".htmlspecialchars($layout["min-rows"]);
    if (in_array("required", $layout["opts"]))
      echo " required=\"required\"";
    echo ">";
    echo newTemplatePattern($ctrl, htmlspecialchars($value));
    echo "</textarea>";
  }
}

function getFileLink($file, $antrag) {
  global $URIBASE;
  $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/anhang/".$file["id"];
  return "<a class=\"show-file-name\" href=\"".htmlspecialchars($target)."\">".htmlspecialchars($file["filename"])."</a>";
}

function renderFormItemFile($layout, $ctrl) {
  $noForm = in_array("no-form", $ctrl["render"]);

  $file = false;
  if (isset($ctrl["_values"])) {
    $file = getFormFile($ctrl["name"], $ctrl["_values"]["_anhang"]);
  }
  $html = "";
  $fileName = "";
  if ($file) {
    $fileName = $file["filename"];
    $html = getFileLink($file, $ctrl["_values"]);
  }
  $ctrl["_render"]->displayValue = $html;
  $tPattern = newTemplatePattern($ctrl, $html);

  if ($noForm) {
    echo "<div>";
    echo $tPattern;
    echo "</div>";
  } else {
    $oldFieldNameFieldName = "formdata[{$layout["id"]}][oldFieldName]";
    $oldFieldNameFieldNameOrig = $oldFieldNameFieldName;
    foreach($ctrl["suffix"] as $suffix) {
      $oldFieldNameFieldName .= "[{$suffix}]";
      $oldFieldNameFieldNameOrig .= "[]";
    }
    $oldFieldName = "<input type=\"hidden\" name=\"".htmlspecialchars($oldFieldNameFieldName)."\" orig-name=\"".htmlspecialchars($oldFieldNameFieldNameOrig)."\" id=\"".htmlspecialchars($ctrl["id"])."-oldFieldName\" value=\"".htmlspecialchars(getFormName($ctrl["name"]))."\"/>";

    $myOut = "<div class=\"single-file-container\">";
    $myOut .= "<input class=\"form-control single-file\" type=\"file\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"/>";
    $myOut .= $oldFieldName;
    $myOut .= "</div>";
    if ($file) {
      $renameFileFieldName = "formdata[{$layout["id"]}][newFileName]";
      $renameFileFieldNameOrig = $renameFileFieldName;
      foreach($ctrl["suffix"] as $suffix) {
        $renameFileFieldName .= "[{$suffix}]";
        $renameFileFieldNameOrig .= "[]";
      }

      echo "<div class=\"single-file-container\" data-display-text=\"".newTemplatePattern($ctrl, $fileName)."\" data-filename=\"".newTemplatePattern($ctrl, $fileName)."\" data-orig-filename=\"".newTemplatePattern($ctrl, $fileName)."\" data-old-html=\"".htmlspecialchars($myOut)."\">";
      echo "<span>".$tPattern."</span>";
      echo "<span>&nbsp;</span>";
      echo "<small><nobr class=\"show-file-size\">".newTemplatePattern($ctrl, $file["size"])."</nobr></small>";
      echo "<a href=\"#\" class=\"on-click-rename-file\"><i class=\"fa fa-fw fa-pencil\"></i></a>";
      echo "<a href=\"#\" class=\"on-click-delete-file\"><i class=\"fa fa-fw fa-trash\"></i></a>";
      echo "<input type=\"hidden\" name=\"".htmlspecialchars($renameFileFieldName)."\" orig-name=\"".htmlspecialchars($renameFileFieldNameOrig)."\" id=\"".htmlspecialchars($ctrl["id"])."-newFileName\" value=\"\" class=\"form-file-name\"/>";
      echo $oldFieldName;
      echo "</div>";
    } else {
      echo $myOut;
    }
  }
}

function renderFormItemMultiFile($layout, $ctrl) {
  $noForm = in_array("no-form", $ctrl["render"]);

  if ($noForm && isset($layout["destination"]))
    return false; // no data here

  $files = false;
  if (isset($ctrl["_values"])) {
    $files = getFormFiles($ctrl["name"], $ctrl["_values"]["_anhang"]);
  }
  $html = [];
  if (is_array($files)) {
    foreach($files as $file) {
      $html[] = getFileLink($file, $ctrl["_values"]);
    }
  }
  $ctrl["_render"]->displayValue = implode(", ",$html);

  if ($noForm) {
    if (isset($layout["destination"])) return false; // no data here

    echo "<div>";
    if (count($html) > 0) {
      echo newTemplatePattern($ctrl, "<ul><li>".implode("</li><li>",$html)."</li></ul>");;
    }
    echo "</div>";
    return;
  }

  echo "<div";
  if (isset($layout["destination"])) {
    $cls = ["multi-file-container", "multi-file-container-with-destination"];
    if (in_array("update-ref", $layout["opts"]))
      $cls[] = "multi-file-container-update-ref";
    $layout["destination"] = str_replace(".", "-", $layout["destination"]);

    echo " class=\"".implode(" ", $cls)."\"";
    echo " data-destination=\"".htmlspecialchars($layout["destination"])."\"";
  } else {
    echo " class=\"multi-file-container multi-file-container-without-destination\"";
  }
  echo ">";

  if (count($html) > 0) {
    echo "<ul>";
    foreach($files as $i => $file) {
      $oldFieldNameFieldName = "formdata[{$layout["id"]}][oldFieldName]";
      $oldFieldNameFieldNameOrig = $oldFieldNameFieldName;
      foreach($ctrl["suffix"] as $suffix) {
        $oldFieldNameFieldName .= "[{$suffix}]";
        $oldFieldNameFieldNameOrig .= "[]";
      }
      $oldFieldNameFieldName .= "[]";
      $oldFieldNameFieldNameOrig .= "[]";
      $oldFieldName = "<input type=\"hidden\" name=\"".htmlspecialchars($oldFieldNameFieldName)."\" orig-name=\"".htmlspecialchars($oldFieldNameFieldNameOrig)."\" id=\"".htmlspecialchars($ctrl["id"])."-oldFieldName\" value=\"".htmlspecialchars($file["fieldname"])."\"/>";

      $renameFileFieldName = "formdata[{$layout["id"]}][newFileName]";
      $renameFileFieldNameOrig = $renameFileFieldName;
      foreach($ctrl["suffix"] as $suffix) {
        $renameFileFieldName .= "[{$suffix}]";
        $renameFileFieldNameOrig .= "[]";
      }
      $renameFileFieldName .= "[]";
      $renameFileFieldNameOrig .= "[]";

      $fileName = $file["filename"];

      echo "<li class=\"multi-file-container-olddata-singlefile\" data-display-text=\"".newTemplatePattern($ctrl, $fileName)."\" data-filename=\"".newTemplatePattern($ctrl, $fileName)."\" data-orig-filename=\"".newTemplatePattern($ctrl, $fileName)."\">";
      echo "<span>".newTemplatePattern($ctrl, $html[$i])."</span>";
      echo "<span>&nbsp;</span>";
      echo "<small><nobr class=\"show-file-size\">".newTemplatePattern($ctrl, $file["size"])."</nobr></small>";
      echo "<a href=\"#\" class=\"on-click-rename-file\"><i class=\"fa fa-fw fa-pencil\"></i></a>";
      echo "<a href=\"#\" class=\"on-click-delete-file\"><i class=\"fa fa-fw fa-trash\"></i></a>";
      echo "<input type=\"hidden\" name=\"".htmlspecialchars($renameFileFieldName)."\" orig-name=\"".htmlspecialchars($renameFileFieldNameOrig)."\" id=\"".htmlspecialchars($ctrl["id"])."-newFileName\" value=\"\" class=\"form-file-name\"/>";
      echo $oldFieldName;
      echo "</li>";
    }
    echo "</ul>";
  }

  echo "<input class=\"form-control multi-file\" type=\"file\" name=\"".htmlspecialchars($ctrl["name"])."[]\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\"[] id=\"".htmlspecialchars($ctrl["id"])."\" multiple";
  if (in_array("dir", $layout["opts"])) {
    echo " webkitdirectory";
  }
  echo "/>";
  echo "</div>";
}

function getTrText($trName, $ctrl) {
  $matches = [];
  $origValue = $trName;

  if ($trName == "")
    return "";

  if (!preg_match('/^(.*)\[([0-9]+)\]$/', $trName, $matches)) {
    return newTemplatePattern($ctrl, htmlspecialchars("miss row idx: ".$trName));
  }

  $currentTable = $matches[1];
  $value = $matches[1];
  $currentRow = (int) $matches[2];

  $txtTr = [ "[$currentRow] <{rowTxt:".$currentTable."[".$currentRow."]}>" ];
  while (preg_match('/^(.*)\[([0-9]+)\]$/', $value, $matches)) {
    if (!isset($ctrl["_render"]->parentMap[$currentTable])) {
      echo "$origValue evaluated to $currentTable which has no parent<br/>\n";
      echo "<pre>";print_r($ctrl["_render"]->parentMap); echo"</pre>\n";
      break;
    }
    $currentTable = $ctrl["_render"]->parentMap[$currentTable];
    $currentRow = (int) $matches[2];
    $value = $matches[1];
    if (!isset($ctrl["_render"]->templates["<{rowTxt:".$currentTable."[".$currentRow."]}>"])) {
      echo "$origValue evaluated to $currentTable and $currentRow which has no text<br/>\n";
      echo "<pre>";print_r($ctrl["_render"]->templates); echo"</pre>\n";
    } else { /* might not be a table */
      array_unshift($txtTr, "[$currentRow] <{rowTxt:".$currentTable."[".$currentRow."]}>");
    }
  }

  return implode(" ", $txtTr);
}

function renderFormItemSelect($layout, $ctrl) {
  global $attributes, $GremiumPrefix;

  $noForm = in_array("no-form", $ctrl["render"]);
  $noFormMarkup = in_array("no-form-markup", $ctrl["render"]);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  }

  if ($noForm) {
    if (isset($layout["data-source"]) && in_array($layout["data-source"], [ "own-orgs", "own-mailinglists" ]) && $layout["type"] != "ref") {
      if ($noFormMarkup)
        echo "<div class=\"visible-inline\">";
      else
        echo "<div class=\"form-control\">";
      echo newTemplatePattern($ctrl, htmlspecialchars($value));
      echo "</div>";
      $ctrl["_render"]->displayValue = htmlspecialchars($value);
    } else if ($layout["type"] == "ref") {
      $tPattern = newTemplatePattern($ctrl, htmlspecialchars("<{ref:$value}>"));
      echo "<div>";
      echo $tPattern;
      echo "</div>";
      if ($value != "") {
        $ctrl["_render"]->referencedBy[$value][] = $ctrl["_render"]->currentParent."[".$ctrl["_render"]->currentParentRow."]";
      }
      $ctrl["_render"]->postHooks[] = function($ctrl) use ($tPattern, $value) {
        $txtTr = getTrText($value, $ctrl);
        $ctrl["_render"]->templates[$tPattern] = processTemplates($txtTr, $ctrl); // rowTxt is from displayValue and thus already escaped
      };
    } else {
      echo "<div class=\"form-control\">";
      echo "**not implemented**";
      echo "</div>";
    }
    return;
  }

  $liveSearch = true;
  if (isset($layout["data-source"]) && $layout["data-source"] == "own-orgs")
    $liveSearch = false;

  $cls = ["select-picker-container"];
  if (in_array("hasFeedback", $layout["opts"]))
    $cls[] = "hasFeedback";
  echo "<div class=\"".implode(" ", $cls)."\">";
  if (in_array("hasFeedback", $layout["opts"]))
    echo '<span class="glyphicon form-control-feedback" aria-hidden="true"></span>';
  echo "<select class=\"selectpicker form-control\" data-live-search=\"".($liveSearch ? "true" : "false")."\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  if (isset($layout["placeholder"]))
    echo " title=\"".htmlspecialchars($layout["placeholder"])."\"";
  elseif ($layout["type"] == "ref")
    echo " title=\"".htmlspecialchars("Bitte auswählen")."\"";
  if (in_array("multiple", $layout["opts"]))
    echo " multiple";
  if (in_array("required", $layout["opts"]))
    echo " required=\"required\"";
  if ($layout["type"] == "ref") {
    $layout["references"] = str_replace(".", "-", $layout["references"]);
    echo " data-references=\"".htmlspecialchars($layout["references"])."\"";
  }
  if ($value != "") {
    $tPattern = newTemplatePattern($ctrl, htmlspecialchars($value));
    echo " data-value=\"{$tPattern}\"";
  }
  echo ">";

  if (isset($layout["data-source"]) && $layout["data-source"] == "own-orgs" && $layout["type"] != "ref") {
    $gremien = $attributes["gremien"];
    if ($value != "" && !in_array($value, $attributes["gremien"]))
      $gremien[] = $value;
    sort($gremien, SORT_STRING | SORT_FLAG_CASE);
    foreach ($GremiumPrefix as $prefix) {
      echo "<optgroup label=\"".htmlspecialchars($prefix)."\">";
      foreach ($gremien as $gremium) {
        if (substr($gremium, 0, strlen($prefix)) != $prefix) continue;
        echo "<option>".htmlspecialchars($gremium)."</option>";
      }
      echo "</optgroup>";
    }
  }
  if (isset($layout["data-source"]) && $layout["data-source"] == "own-mailinglists" && $layout["type"] != "ref") {
    $mailinglists = $attributes["mailinglists"];
    if ($value != "" && !in_array($value, $attributes["mailinglists"]))
      $mailinglists[] = $value;
    sort($mailinglists, SORT_STRING | SORT_FLAG_CASE);
    foreach ($mailinglists as $mailinglist) {
      echo "<option>".htmlspecialchars($mailinglist)."</option>";
    }
  }
  if ($layout["type"] == "ref")
    echo "<option value=\"\">Bitte auswählen</option>";

  echo "</select>";
  echo "</div>";
}

function renderFormItemDateRange($layout, $ctrl) {
  $noForm = in_array("no-form", $ctrl["render"]);
  $noFormMarkup = in_array("no-form-markup", $ctrl["render"]);

  $valueStart = "";
  $valueEnd = "";
  if (isset($ctrl["_values"])) {
    $valueStart = getFormValue($ctrl["name"]."[start]", $layout["type"], $ctrl["_values"]["_inhalt"], $valueStart);
    $valueEnd = getFormValue($ctrl["name"]."[end]", $layout["type"], $ctrl["_values"]["_inhalt"], $valueEnd);
  }
  $tPatternStart = newTemplatePattern($ctrl, htmlspecialchars($valueStart));
  $tPatternEnd =  newTemplatePattern($ctrl, htmlspecialchars($valueEnd));
  $ctrl["_render"]->displayValue = htmlspecialchars("$valueStart - $valueEnd");

  if ($noForm && !$noFormMarkup) {
    echo '<div class="input-daterange input-group">';
    echo '<div class="input-group-addon" style="background-color: transparent; border: none;">von</div>';
    echo "<div class=\"form-control\">{$tPatternStart}</div>";
    echo '<div class="input-group-addon" style="background-color: transparent; border: none;">bis</div>';
    echo "<div class=\"form-control\">{$tPatternEnd}</div>";
    echo "</div>";
    return;
  } else if ($noForm && $noFormMarkup) {
    echo "<div class=\"visible-inline\">";
    if ($valueStart != "") {
      echo ' von ';
      echo "{$tPatternStart}";
    }
    if ($valueEnd != "") {
      echo ' bis ';
      echo "{$tPatternEnd}";
    }
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
  if (in_array("not-before-creation", $layout["opts"])) {
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
          <input type="text" class="input-sm form-control" name="<?php echo htmlspecialchars($ctrl["name"]); ?>[start]" orig-name="<?php echo htmlspecialchars($ctrl["orig-name"]); ?>[start]" <?php echo (in_array("required", $layout["opts"]) ? "required=\"required\"": ""); ?> value="<?php echo $tPatternStart; ?>"/>
          <div class="input-group-addon">
            <span class="glyphicon glyphicon-th"></span>
          </div>
        </div>
        <div class="input-group-addon" style="background-color: transparent; border: none;">
          bis
        </div>
        <div class="input-group">
          <input type="text" class="input-sm form-control" name="<?php echo htmlspecialchars($ctrl["name"]); ?>[end]" orig-name="<?php echo htmlspecialchars($ctrl["orig-name"]); ?>[end]" <?php echo (in_array("required", $layout["opts"]) ? "required=\"required\"": ""); ?> value="<?php echo $tPatternEnd; ?>"/>
          <div class="input-group-addon">
            <span class="glyphicon glyphicon-th"></span>
          </div>
        </div>
    </div>
<?php

}


function renderFormItemDate($layout, $ctrl) {
  $noForm = in_array("no-form", $ctrl["render"]);
  $noFormMarkup = in_array("no-form-markup", $ctrl["render"]);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  }
  $tPattern = newTemplatePattern($ctrl, htmlspecialchars($value));
  $ctrl["_render"]->displayValue = htmlspecialchars($value);

  if ($noForm) {
    $cls = [];
    if (!$noFormMarkup)
      $cls[] = "form-control";
    else
      $cls[] = "visible-inline";
    echo "<div class=\"".htmlspecialchars(implode(" ", $cls))."\">";
    echo $tPattern;
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
  if (in_array("not-before-creation", $layout["opts"])) {
?>
     data-date-start-date="today"
<?php
  }
?>
>
    <input type="text" class="form-control" name="<?php echo htmlspecialchars($ctrl["name"]); ?>" orig-name="<?php echo htmlspecialchars($ctrl["orig-name"]); ?>" id="<?php echo htmlspecialchars($ctrl["id"]); ?>" <?php echo (in_array("required", $layout["opts"]) ? "required=\"required\"": ""); ?> value="<?php echo $tPattern; ?>"/>
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

function renderFormItemTable($layout, $ctrl) {
  $withRowNumber = in_array("with-row-number", $layout["opts"]);
  $noForm = in_array("no-form", $ctrl["render"]);

  $cls = ["table", "table-striped", "summing-table"];
  if (!$noForm)
    $cls[] = "dynamic-table";
  if (in_array("fixed-width-table", $layout["opts"]))
    $cls[] = "fixed-width-table";

  $rowCountFieldName = (isset($layout["rowCountField"]) ? "formdata[{$layout["rowCountField"]}]" : "formdata[{$layout["id"]}][rowCount]");
  $rowCountFieldNameOrig = $rowCountFieldName;
  $rowCountFieldTypeName = (isset($layout["rowCountField"]) ? "formtype[{$layout["rowCountField"]}]" : "formtype[{$layout["id"]}]");
  foreach($ctrl["suffix"] as $suffix) {
    $rowCountFieldName .= "[{$suffix}]";
    $rowCountFieldNameOrig .= "[]";
  }

  $rowCount = 0;
  if (isset($ctrl["_values"])) {
    $rowCount = (int) getFormValue($rowCountFieldName, $layout["type"], $ctrl["_values"]["_inhalt"], $rowCount);
  }
  if ($noForm && $rowCount == 0) return false; //empty table

  $myParent = $ctrl["_render"]->currentParent;
  $myParentRow = $ctrl["_render"]->currentParentRow;
  if ($myParent !== false)
    $ctrl["_render"]->parentMap[getFormName($ctrl["name"])] = $myParent;
  $ctrl["_render"]->currentParent = getFormName($ctrl["name"]);

  $hasPrintSumFooter = false;

?>

  <table class="<?php echo implode(" ", $cls); ?>" id="<?php echo htmlspecialchars($ctrl["id"]); ?>" name="<?php echo htmlspecialchars($ctrl["name"]); ?>" orig-name="<?php echo htmlspecialchars($ctrl["orig-name"]); ?>">

<?php
  if (!$noForm) {
    echo "<input type=\"hidden\" value=\"".htmlspecialchars($rowCount)."\" name=\"".htmlspecialchars($rowCountFieldName)."\" orig-name=\"".htmlspecialchars($rowCountFieldNameOrig)."\" class=\"store-row-count\"/>";
    echo "<input type=\"hidden\" value=\"".htmlspecialchars($layout["type"])."\" name=\"".htmlspecialchars($rowCountFieldTypeName)."\"/>";
  }

  if (in_array("with-headline", $layout["opts"])) {

?>

    <thead>
      <tr>
<?php
        echo "<th></th>";
        if ($withRowNumber) {
          echo "<th></th>";
        }
        foreach ($layout["columns"] as $col) {
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
     $addToSumValueBeforeTable = $ctrl["_render"]->addToSumValue;
     if (!$noForm)
       $rowCountPrint = $rowCount+1;
     else
       $rowCountPrint = $rowCount;

     for ($rowNumber = 0; $rowNumber < $rowCountPrint; $rowNumber++) { # this prints $rowCount +1 rows --> extra template row
       $cls = ["dynamic-table-row"];
       if ($rowNumber == $rowCount)
         $cls[] = "new-table-row";
       $newSuffix = $ctrl["suffix"];
       if ($rowNumber == $rowCount)
         $newSuffix[] = false;
       else
         $newSuffix[] = $rowNumber;
       $ctrl["_render"]->displayValue = false;
       $ctrl["_render"]->currentParentRow = $rowNumber;
       $addToSumValueBeforeRow = $ctrl["_render"]->addToSumValue;
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

        foreach ($layout["columns"] as $i => $col) {
          if (!isset($col["opts"]))
            $col["opts"] = [];

          $tdClass = [ "{$ctrl["id"]}-col-$i" ];
          if (in_array("title", $col["opts"]))
            $tdClass[] = "dynamic-table-column-title";
          else
            $tdClass[] = "dynamic-table-column-no-title";
          if (in_array("sum-over-table-bottom", $col["opts"])) {
            $col["addToSum"][] = "col-sum-".$layout["id"]."-".$i;
            $hasPrintSumFooter |= true;
          }
          if (!empty($col["printSumFooter"]))
            $hasPrintSumFooter |= true;

          $newCtrl = ["wrapper"=> "td", "suffix" => $newSuffix, "class" => $tdClass ];
          if ($noForm)
            $ctrl["_render"]->displayValue = false;

          renderFormItem($col, array_merge($ctrl, $newCtrl));

          if (in_array("title", $col["opts"]))
            $rowTxt[] = $ctrl["_render"]->displayValue;
        }

        $refname = getFormName($ctrl["name"]);
        $ctrl["_render"]->templates["<{rowTxt:".$refname."[".$rowNumber."]}>"] = implode(", ", $rowTxt);

        $addToSumDifference = [];
        foreach($ctrl["_render"]->addToSumValue as $addToSumId => $sum) {
          if (isset($addToSumValueBeforeRow[$addToSumId]))
            $before = $addToSumValueBeforeRow[$addToSumId];
          else
            $before = 0.00;
          $addToSumDifference[$addToSumId] = $sum - $before;
        }
        $ctrl["_render"]->addToSumValueByRowRecursive[$refname."[".$rowNumber."]"] = $addToSumDifference;

?>
       </tr>
<?php
     }
?>
    </tbody>
<?php
    if ($hasPrintSumFooter) {
        $addToSumDifference = [];
        foreach($ctrl["_render"]->addToSumValue as $addToSumId => $sum) {
          if (isset($addToSumValueBeforeTable[$addToSumId]))
            $before = $addToSumValueBeforeTable[$addToSumId];
          else
            $before = 0.00;
          $addToSumDifference[$addToSumId] = $sum - $before;
        }
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
        foreach ($layout["columns"] as $i => $col) {
          if (!isset($col["opts"])) $col["opts"] = [];
          if (in_array("sum-over-table-bottom", $col["opts"])) {
            $col["printSumFooter"][] = "col-sum-".$layout["id"]."-".$i;
          }
          if (isset($col["printSumFooter"]) && count($col["printSumFooter"]) > 0) {
?>
        <th class="cell-has-printSum">
<?php
            foreach ($col["printSumFooter"] as $psId) {
              if (isset($ctrl["_render"]->addToSumMeta[$psId])) {
                $newMeta = $ctrl["_render"]->addToSumMeta[$psId];
              } else {
                $newMeta = $col;
              }
              unset($newMeta["addToSum"]);
              if (isset($addToSumDifference[$psId]))
                $value = $addToSumDifference[$psId];
              else
                $value = 0.00;
              $value = number_format($value, 2, ".", "");
              $newMeta["value"] = $value;
              $newMeta["opts"][] = "is-sum";
              $newMeta["printSum"] = [ $psId ];

              $newCtrl = $ctrl;
              $newCtrl["suffix"][] = "print-foot";
              $newCtrl["suffix"][] = $layout["id"];
              $newCtrl["render"][] = "no-form";
              unset($newCtrl["_values"]);
              renderFormItem($newMeta, $newCtrl);
            }
          } else {
?>
        <th>
<?php
          }
?>
        </th>
<?php
        }
?>
      </tr>
    </tfoot>
<?php
    } /* if has column sums */
?>
  </table>
<?
  $ctrl["_render"]->displayValue = false;
  $ctrl["_render"]->currentParent = $myParent;
  $ctrl["_render"]->currentParentRow = $myParentRow;

}

function renderFormItemInvRef($layout,$ctrl) {
  $refId = $ctrl["_render"]->currentParent."[".$ctrl["_render"]->currentParentRow."]";
  $tPattern = newTemplatePattern($ctrl, htmlspecialchars("<{invref:".uniqid().":".$refId."}>"));
  echo $tPattern;
  $ctrl["_render"]->templates[$tPattern] = htmlspecialchars("{".$tPattern."}"); // fallback
  $ctrl["_render"]->postHooks[] = function($ctrl) use ($tPattern, $layout, $refId, $ctrl) {
    $noForm = in_array("no-form", $ctrl["render"]);
    if (isset($layout["printSum"]))
      $printSum = $layout["printSum"];
    else
      $printSum = [];

    $myOut = "<table class=\"table table-striped invref summing-table\" id=\"".htmlspecialchars($ctrl["id"])."\" name=\"".htmlspecialchars($ctrl["name"])."\"  orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\">\n";
    $myOut .= "  <tbody>\n";

    if ($noForm) {
      if (isset($ctrl["_render"]->referencedBy[$refId]))
        $referencingMe = $ctrl["_render"]->referencedBy[$refId];
      else
        $referencingMe = [];

      $columnSum = [];

      foreach ($referencingMe as $referencingRow) {
        $txtTr = getTrText($referencingRow, $ctrl);

        $myOut .= "    <tr>\n";
        $myOut .= "      <td class=\"invref-txtTr\">{$txtTr}</td>\n"; /* Spalte: Quelle */

        foreach ($printSum as $psId) {
          $value = $ctrl["_render"]->addToSumValueByRowRecursive[$referencingRow][$psId];
          $value = number_format($value, 2, ".", "");
          if (isset($ctrl["_render"]->addToSumMeta[$psId])) {
            $newMeta = $ctrl["_render"]->addToSumMeta[$psId];
            $newMeta["addToSum"] = [ "invref-".$layout["id"]."-".$psId ];
            $newMeta["printSum"] = [ $psId ];
            $newMeta["value"] = $value;
            if (!isset($columnSum[ $psId ]))
              $columnSum[ $psId ] = 0.00;
            $columnSum[ $psId ] += (float) $value;

            $newCtrl = array_merge($ctrl, ["wrapper"=> "td", "class" => [ "cell-has-printSum" ] ]);
            $newCtrl["suffix"][] = "print";
            $newCtrl["suffix"][] = $layout["id"];
            $newCtrl["render"][] = "no-form";
            unset($newCtrl["_values"]);
            ob_start();
            renderFormItem($newMeta, $newCtrl);
            $myOut .= ob_get_contents();
            ob_end_clean();
          } else {
            $myOut .= "    <td class=\"cell-has-printSum\">";
            $myOut .= "      <div data-printSum=\"".htmlspecialchars($psId)."\">".htmlspecialchars($value)."</div>";
            $myOut .= "    </td>\n";
          }
        }
        $myOut .= "    </tr>\n";
      }
    } else {
      $myOut .= "    <tr class=\"invref-template summing-skip\">\n";
      $myOut .= "      <td class=\"invref-rowTxt\"></td>\n"; /* Spalte: Quelle */

      foreach ($printSum as $psId) {
        if (isset($ctrl["_render"]->addToSumMeta[$psId])) {
          $newMeta = $ctrl["_render"]->addToSumMeta[$psId];
          $newMeta["addToSum"] = [ "invref-".$layout["id"]."-".$psId ];
          $newMeta["printSum"] = [ $psId ];

          $newCtrl = array_merge($ctrl, ["wrapper"=> "td", "class" => [ "cell-has-printSum" ] ]);
          $newCtrl["suffix"][] = "print";
          $newCtrl["suffix"][] = $layout["id"];
          $newCtrl["render"][] = "no-form";
          unset($newCtrl["_values"]);
          ob_start();
          renderFormItem($newMeta, $newCtrl);
          $myOut .= ob_get_contents();
          ob_end_clean();
        } else {
          $myOut .= "    <td class=\"cell-has-printSum\">";
            $myOut .= "    <div data-printSum=\"".htmlspecialchars($psId)."\">no meta data for ".htmlspecialchars($psId)."</div>";
          $myOut .= "    </td>\n";
        }
      }

      $myOut .= "    </tr>\n";
    }

    $myOut .= "  </tbody>\n";
    $myOut .= "  <tfoot>\n";
    $myOut .= "    <tr>\n";
    $myOut .= "      <td></td>\n"; /* Spalte: Quelle */
    foreach ($printSum as $psId) {
      if (isset($ctrl["_render"]->addToSumMeta[$psId])) {
        $newMeta = $ctrl["_render"]->addToSumMeta[$psId];
        unset($newMeta["addToSum"]);
        $newMeta["printSum"] = [ "invref-".$layout["id"]."-".$psId ];
        if (!isset($columnSum[ $psId ]))
          $columnSum[ $psId ] = 0.00;
        $newMeta["value"] = number_format($columnSum[ $psId ], 2, ".", "");
        $newMeta["opts"][] = "is-sum";

        $newCtrl = array_merge($ctrl, ["wrapper"=> "th", "class" => [ "cell-has-printSum" ] ]);
        $newCtrl["suffix"][] = "print-foot";
        $newCtrl["suffix"][] = $layout["id"];
        $newCtrl["render"][] = "no-form";
        unset($newCtrl["_values"]);
        ob_start();
        renderFormItem($newMeta, $newCtrl);
        $myOut .= ob_get_contents();
        ob_end_clean();
      } else {
        $myOut .= "    <td class=\"cell-has-printSum\">";
          $myOut .= "    <div printSum=\"".htmlspecialchars($psId)."\">no meta data for ".htmlspecialchars($psId)."</div>";
        $myOut .= "    </td>\n"; /* Spalte: Quelle */
      }
    }

    $myOut .= "    </tr>\n";
    $myOut .= "  </tfoot>\n";
    $myOut .= "</table>\n";
    $ctrl["_render"]->templates[$tPattern] = processTemplates($myOut, $ctrl); // rowTxt is from displayValue and thus already escaped
  };
}
