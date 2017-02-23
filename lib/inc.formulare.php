<?php

loadForms();

function convertDBValueToUserValue($value, $type) {
  switch ($type) {
    case "money":
      if ($value === false || $value == "") return $value;
      return number_format($value, 2, ',', ' ');
    default:
      return $value;
  }
}

function convertUserValueToDBValue($value, $type) {
  switch ($type) {
    case "titelnr":
      $value = trim(str_replace(" ", "", $value));
      $nv = "";
      for ($i = 0; $i < strlen($value); $i++) {
        if ($i % 4 == 1) $nv .= " ";
        $nv .= $value[$i];
      }
      return $nv;
    case "money":
      return str_replace(" ", "", str_replace(",",".",str_replace(".", "", $value)));
    default:
      return $value;
  }
}

function registerForm( $type, $revision, $layout, $config ) {
  global $formulare;

  if (!isset($formulare[$type])) die("missing form-class $type");
  if (isset($formulare[$type][$revision])) die("duplicate form-id $type:$revision");
  $formulare[$type][$revision] = [
    "layout" => $layout,
    "config" => $config,
    "type" => $type,
    "revision" => $revision,
    "_class" => $formulare[$type]["_class"],
    "_perms" => mergePermission($formulare[$type]["_class"], $config, $type, $revision),
  ];
}

function mergePermission($classConfig, $revConfig, $type, $revision) {
  $perms = [];
  foreach ([$classConfig, $revConfig] as $config) {
    if (!isset($config["permission"])) continue;
    foreach ($config["permission"] as $id => $p) {
      if (isset($perms[$id])) die("$type:$revision: permission $id has conflicting definitions");
      $perms[$id] = $p;
    }
  }
  return $perms;
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

function checkSinglePermission(&$i, &$c, &$antrag, &$form) {
  global $attributes;
  if ($i == "state") {
    $currentState = "draft";
    if (isset($form["_class"]["createState"]))
      $currentState = $form["_class"]["createState"];
    if ($antrag)
      $currentState = $antrag["state"];
    if ($currentState != $c)
      return false;
  } else if ($i == "creator") {
    if ($c == "self") {
      if ($antrag !== null && isset($antrag["creator"]) && ($antrag["creator"] != getUsername()))
        return false;
    } else {
      die("unkown creator test: $c");
    }
  } else if (substr($i,0,12) == "inOtherForm:") {
    $fieldDesc = substr($i, 12);
    $fieldValue = false;
    $fieldName = false;
    if ($fieldDesc == "referenceField") {
      if (!isset($form["config"]["referenceField"])) return false; #no such field
      $fieldName = $form["config"]["referenceField"]["name"];
    } elseif (substr($fieldDesc,0,6) == "field:") {
      $fieldName = substr($fieldDesc,6);
    } else {
      die ("inOtherForm: fieldDesc=$fildDesc not implemented");
    }
    if ($fieldValue === false && $fieldName !== false && $antrag !== null && isset($antrag["_inhalt"]))
      $fieldValue = getFormValueInt($fieldName, null, $antrag["_inhalt"], $fieldValue);
#    echo "\n<!-- checkSinglePermission: {$form["type"]} {$form["revision"]} ".($antrag === null ? "w/o antrag":"w antrag")." i=$i c=".json_encode($c).": fieldName = $fieldName fieldValue = ".(print_r($fieldValue,true))." -->\n";
    if ($fieldValue === false || $fieldValue == "")
      return false; # nothing given here
    $otherAntrag = getAntrag($fieldValue);
#    echo "\n<!-- checkSinglePermission: {$form["type"]} {$form["revision"]} ".($antrag === null ? "w/o antrag":"w antrag")." i=$i c=".json_encode($c).": otherAntrag = ".($otherAntrag === false ? "false" : "non-false")." -->\n";
    if ($otherAntrag === false) return false; # not readable. Ups.
    $otherForm =  getForm($otherAntrag["type"], $otherAntrag["revision"]);

    if (!is_array($c)) $c = [$c];
    foreach ($c as $permName) {
#      echo "\n<!-- checkSinglePermission: {$form["type"]} {$form["revision"]} ".($antrag === null ? "w/o antrag":"w antrag")." i=$i c=".json_encode($c).": evaluate $permName -->\n";
      if (!hasPermission($otherForm, $otherAntrag, $permName))
        return false;
    }
  } else if ($i == "hasPermission") {
    if (!is_array($c)) $c = [$c];
    foreach ($c as $permName) {
      if (!hasPermission($form, $antrag, $permName))
        return false;
    }
  } else if ($i == "group") {
    if (!is_array($c)) $c = [$c];
    foreach ($c as $groupName) {
      if (!hasGroup($groupName))
        return false;
    }
  } else if (substr($i, 0, 6) == "field:") {
    $fieldName = substr($i, 6);
    if ($antrag !== null && isset($antrag["_inhalt"])) {
      $value = getFormValueInt($fieldName, null, $antrag["_inhalt"], null);
      if (substr($c,0,5) == "isIn:") {
        $in = substr($c,5);
        $permittedValues = [];
        if ($value === null) return false;
        if ($in == "data-source:own-orgs") {
          $permittedValues = $attributes["gremien"];
        } else if ($in == "data-source:own-mail") {
          $permittedValues = array_values($attributes["mail"]);
          if (isset($attributes["extra-mail"]))
            $permittedValues = array_merge($permittedValues, array_values($attributes["extra-mail"]));
        } else {
          die("isIn test $in (from $c) not implemented");
        }
        if (!in_array($value, $permittedValues))
          return false;
      } else {
        die("field test $c not implemented");
      }
    }
    /* antrag === null -> muss erst noch passend ausgefüllt werden (e.g. bei canCreate) */
    /* antrag !== null aber !isset(_inhalt) -> muss erst noch passend ausgefüllt werden (e.g. can alter state before create) */
  } else {
    die("permission type $i not implemented");
  }
  return true;
}

function checkPermissionLine(&$p, &$antrag, &$form) {
  foreach ($p as $i => $c) {
    $tmp = checkSinglePermission($i, $c, $antrag, $form);
#    echo "\n<!-- checkSinglePermission: {$form["type"]} {$form["revision"]} ".($antrag === null ? "w/o antrag":"w antrag")." i=$i c=".json_encode($c)." => ".($tmp ? "true":"false")." -->\n";
    if (!$tmp)
      return false;
  }
  return true;
}

function hasPermission(&$form, $antrag, $permName) {
  global $formulare;
  static $stack = false;

  if (!isset($form["_perms"][$permName]))
    return false;

  $pp = $form["_perms"][$permName];
  if ($antrag === null || !isset($antrag["id"]))
    $aId = "null";
  else
    $aId = $antrag["id"];

  $permId = $form["type"].":".$form["revision"].":".$aId.".".$permName;
  if ($stack === false)
    $stack = [];
  if (in_array($permId, $stack))
    return false;
  array_push($stack, $permId);

#  echo "\n<!-- hasPermission: {$form["type"]} {$form["revision"]} ".($antrag === null ? "w/o antrag":"w antrag")." $permName => to be evaluated -->\n";

  $ret = hasPermissionImpl($form, $antrag, $pp, $permName);

  array_pop($stack);

#  echo "\n<!-- hasPermission: {$form["type"]} {$form["revision"]} ".($antrag === null ? "w/o antrag":"w antrag")." $permName => ".($ret ? "true":"false")." -->\n";

  return $ret;
}

function hasPermissionImpl(&$form, &$antrag, &$pp, $permName = "anonymous") {
  global $ADMINGROUP;

  if (hasGroup($ADMINGROUP))
    return true;

  $ret = false;

  if (is_bool($pp))
    $ret = $pp;

  if (is_array($pp)) {
    foreach($pp as $i => $p) {
      $tmp = checkPermissionLine($p, $antrag, $form);
#      echo "\n<!-- checkPermissionLine: {$form["type"]} {$form["revision"]} ".($antrag === null ? "w/o antrag":"w antrag")." i=$i p=".json_encode($p)." => ".($tmp ? "true":"false")." -->\n";
      if (!$tmp)
        continue;
      $ret = true;
      break;
    }
  }

#  echo "\n<!-- hasPermissionImpl: {$form["type"]} {$form["revision"]} ".($antrag === null ? "w/o antrag":"w antrag")." $permName => ".($ret ? "true":"false")." -->\n";

  return $ret;
}

function getForm($type, $revision) {
  global $formulare;

  if (!isset($formulare[$type])) return false;
  if (!isset($formulare[$type][$revision])) return false;

  return $formulare[$type][$revision];
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

function getBaseName($name) {
  $matches = [];
  if (preg_match("/^([^\[\]]*)(.*)/", $name, $matches)) {
    return $matches[1];
  }
  return false;
}

function getFormName($name) {
  $matches = [];
  if (preg_match("/^formdata\[([^\]]*)\](.*)/", $name, $matches)) {
    return $matches[1].$matches[2];
  }
  return false;
}

function getFormNames($name) {
  $matches = [];
  if (preg_match("/^formdata\[([^\]]*)\](.*)/", $name, $matches)) {
    return [ $matches[1], $matches[2] ];
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

function getFormEntries($name, $type, $values, $value = null) {
  $ret = [];
  foreach($values as $row) {
    if ($row["fieldname"] != $name && (substr($row["fieldname"], 0, strlen($name."[")) != $name."["))
      continue;
    if ($type !== null && $row["contenttype"] != $type) {
      add_message("Feld $name: erwarteter Typ = \"$type\", erhaltener Typ = \"{$row["contenttype"]}\"");
      continue;
    }
    if ($value !== null && $row["value"] != $value)
      continue;
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

  renderFormImpl($form, $ctrl);
}

function renderFormImpl(&$form, &$ctrl) {

  static $stack = false;

  if ($stack === false) $stack = [];
  if (isset($ctrl["_values"])) {
    if (in_array($ctrl["_values"]["id"], $stack)) {
      echo "form {$ctrl["_values"]["id"]} already on stack<br>\n";
      return;
    }
    array_push($stack, $ctrl["_values"]["id"]);
  }

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
  $ctrl["_render"]->currentRowId = false;
  $ctrl["_render"]->postHooks = []; /* e.g. ref-field */
  $ctrl["_render"]->addToSumMeta = [];
  $ctrl["_render"]->addToSumValue = [];
  $ctrl["_render"]->addToSumValueByRowRecursive = [];
  $ctrl["_render"]->referencedBy = []; /* tableRowReferenced -> tableRowWhereReferenceIs */
  $ctrl["_render"]->otherForm = [];
  $ctrl["_render"]->numTableRows = [];
  $ctrl["_render"]->rowIdToNumber = [];
  $ctrl["_render"]->rowNumberToId = [];

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

  array_pop($stack);
}


function processTemplates($txt, $ctrl) {
  return str_replace(array_keys($ctrl["_render"]->templates), array_values($ctrl["_render"]->templates), $txt);
}

function isNoForm($layout, $ctrl) {
  $noForm = in_array("no-form", $ctrl["render"]);
  $noFormCb = in_array("no-form-cb", $ctrl["render"]);
  $noFormMarkup = in_array("no-form-markup", $ctrl["render"]);
  if ($noFormCb) {
    $noForm |= $ctrl["no-form-cb"]($layout, $ctrl);
  }
  return Array ($noForm, $noFormMarkup);
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
  $ctrl["orig-id"] = $ctrl["id"];

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
  $ctrl["orig-id"] = str_replace(".", "-", $ctrl["orig-id"]);

  $cls = ["form-group"];
  if (in_array("hasFeedback", $layout["opts"])) $cls[] = "has-feedback";

  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);
  if ($noForm)
    $cls[] = "no-form-grp";
  else
    $cls[] = "form-grp";

  $ctrl["readonly"] = false;
  if (isset($layout["toggleReadOnly"])) {
    /* check readonly state of element, needs to be checkbox or radio */
    list ($elId, $elVal) = $layout["toggleReadOnly"];
    $value = "";
    if (isset($ctrl["_values"])) {
      $value = getFormValueInt($elId, null, $ctrl["_values"]["_inhalt"], $value);
    }
    $isReadOnly = ($elVal != $value);
    $ctrl["readonly"] = $isReadOnly;
  } elseif (in_array("readonly", $layout["opts"]))
    $ctrl["readonly"] = true;

  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);
  if (!$noForm && in_array("hide-edit", $layout["opts"]))
    return;

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
    case "titelnr":
    case "email":
    case "url":
    case "iban":
      $isEmpty = renderFormItemText($layout,$ctrl);
      break;
    case "checkbox":
      $isEmpty = renderFormItemCheckbox($layout,$ctrl);
      break;
    case "radio":
      $isEmpty = renderFormItemRadio($layout,$ctrl);
      break;
    case "otherForm":
      $isEmpty = renderFormItemOtherForm($layout,$ctrl);
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

    if (!$noForm)
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

  $rowTxt = [];

  foreach ($layout["children"] as $child) {
    $ctrl["_render"]->displayValue = true;
    renderFormItem($child, $ctrl);
    if (in_array("title", $child["opts"])) {
      $rowTxt[] = $ctrl["_render"]->displayValue;
    }
  }
  if (in_array("well", $layout["opts"]))
    echo "<div class=\"clearfix\"></div></div>";

  $ctrl["_render"]->displayValue = implode(", ", $rowTxt);

}

function renderFormItemOtherForm($layout,$ctrl) {
  global $URIBASE, $nonce;

  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);
  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  } elseif (isset($layout["value"])) {
    $value = $layout["value"];
  }

  if (!$noForm && $ctrl["readonly"]) {
    $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
    echo " value=\"{$tPattern}\"";
    echo '>';
    $noForm = true;
  }

  if ($noForm) {
    echo '<div>';
    echo '<span class="glyphicon glyphicon glyphicon-link align-top" aria-hidden="true"></span>';

    $otherAntrag = false;
    if ($value === "") {
      echo '<i>Keine Angabe</i>';
    } else {
      $otherAntrag = dbGet("antrag", ["id" => $value]);
      if ($otherAntrag === false) {
        echo "<i>ungültiger Wert: ".newTemplatePattern($ctrl, htmlspecialchars($value))."</i>";
      }
    }

    $readPermitted = false;
    if ($otherAntrag !== false) {
      $otherInhalt = dbFetchAll("inhalt", ["antrag_id" => $otherAntrag["id"]]);
      $otherAntrag["_inhalt"] = $otherInhalt;

      $otherForm = getForm($otherAntrag["type"], $otherAntrag["revision"]);
      $readPermitted = hasPermission($otherForm, $otherAntrag, "canRead");

      if (!$readPermitted) {
        echo "<i>Formular nicht lesbar: ".newTemplatePattern($ctrl, htmlspecialchars($value))."</i>";
      }
    }

    if ($readPermitted) {
      $classTitle = "[{$otherAntrag["type"]}]";
      $classConfig = $otherForm["_class"];
      if (isset($classConfig["title"]))
        $classTitle = $classConfig["title"];
      if (isset($classConfig["shortTitle"]))
        $classTitle = $classConfig["shortTitle"];
      $text = getAntragDisplayTitle($otherAntrag, $otherForm["config"]);
      $target = str_replace("//","/",$URIBASE."/").rawurlencode($otherAntrag["token"]);

      echo "<a href=\"".htmlspecialchars($target)."\" target=\"_blank\">";
      echo newTemplatePattern($ctrl, "{$classTitle}: ".str_replace("\n","<br/>",implode(" ",$text)));
      echo "</a>";
    }

    echo '</div>';
    return;
  }

  $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));
  echo "<div class=\"input-group\">";
  echo "<span class=\"input-group-addon extra-text\"></span>";
  echo "<input class=\"form-control\" type=\"{$layout["type"]}\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  if (in_array("required", $layout["opts"]))
    echo " required=\"required\"";

  echo " data-remote=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=validate.otherForm&nonce=".urlencode($nonce))."\"";
  echo " data-remote-error=\"Ungültige Formularnummer\"";
  echo " data-extra-text=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=text.otherForm&nonce=".urlencode($nonce))."\"";
  echo " value=\"{$tPattern}\"";
  echo '>';
  echo "</div>";
  if (in_array("hasFeedback", $layout["opts"]))
    echo '<span class="glyphicon form-control-feedback" aria-hidden="true"></span>';
#  echo '<div class="extra-text pull-right"></div>';
}

function renderFormItemRadio($layout,$ctrl) {
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  } elseif (isset($layout["value"])) {
    $value = $layout["value"];
  } elseif (!$noForm && isset($layout["prefill"]) && $layout["prefill"] == "user:mail") {
    $value = getUserMail();
  }

  if (!$noForm && $ctrl["readonly"]) {
    if ($value == $layout["value"]) {
      $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));
      echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
      echo " value=\"{$tPattern}\"";
      echo '>';
    }
    $noForm = true;
  }

  if ($noForm) {
    echo '<div class="radio">';
    if ($value == $layout["value"]) {
      #echo '<span class="glyphicon glyphicon-ok-circle align-top" aria-hidden="true"></span>';
      echo '<span class="glyphicon glyphicon-check align-top" aria-hidden="true"></span>';
    } else {
      echo '<span class="glyphicon glyphicon-unchecked align-top" aria-hidden="true"></span>';
    }
    echo '<label>';
    echo str_replace("\n","<br/>",htmlspecialchars($layout["text"]));
    echo '</label>';
    echo '</div>';
    return;
  }

  echo '<div class="radio">';
  echo '<label><input type="radio" name="'.htmlspecialchars($ctrl["name"]).'" value="'.htmlspecialchars($layout["value"]).'"';
  if ($value == $layout["value"]) {
    echo " checked=\"checked\"";
  }
  if (in_array("required", $layout["opts"]))
    echo " required=\"required\"";
  if (in_array("toggleReadOnly", $layout["opts"]))
    echo " data-isToggleReadOnly=\"true\"";
  echo '>'.str_replace("\n","<br/>",htmlspecialchars($layout["text"])).'</label>';
  echo '</div>';
}

function renderFormItemCheckbox($layout,$ctrl) {
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  }

  if (!$noForm && $ctrl["readonly"]) {
    if ($value == $layout["value"]) {
      $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));
      echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
      echo " value=\"{$tPattern}\"";
      echo '>';
    }
    $noForm = true;
  }

  if ($noForm) {
    echo '<div class="checkbox">';
    if ($value == $layout["value"]) {
      #echo '<span class="glyphicon glyphicon-ok-circle align-top" aria-hidden="true"></span>';
      echo '<span class="glyphicon glyphicon-check align-top" aria-hidden="true"></span>';
    } else {
      echo '<span class="glyphicon glyphicon-unchecked align-top" aria-hidden="true"></span>';
    }
    echo '<label>';
    echo str_replace("\n","<br/>",htmlspecialchars($layout["text"]));
    echo '</label>';
    echo '</div>';
    return;
  }

  echo '<div class="checkbox">';
  echo '<label><input type="checkbox" name="'.htmlspecialchars($ctrl["name"]).'" value="'.htmlspecialchars($layout["value"]).'"';
  if ($value == $layout["value"]) {
    echo " checked=\"checked\"";
  }
  if (in_array("required", $layout["opts"]))
    echo " required=\"required\"";
  if (in_array("toggleReadOnly", $layout["opts"]))
    echo " data-isToggleReadOnly=\"true\"";
  echo '>'.str_replace("\n","<br/>",htmlspecialchars($layout["text"])).'</label>';
  echo '</div>';
}

function renderFormItemText($layout, $ctrl) {
  global $nonce, $URIBASE, $attributes, $GremiumPrefix;

  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);
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
  $tPattern = newTemplatePattern($ctrl, htmlspecialchars($value));

  $ctrl["_render"]->displayValue = htmlspecialchars($value);
  if (isset($layout["addToSum"])) {
    foreach ($layout["addToSum"] as $addToSumId) {
      $ctrl["_render"]->addToSumMeta[$addToSumId] = $layout;
      if (!isset($ctrl["_render"]->addToSumValue[$addToSumId]))
        $ctrl["_render"]->addToSumValue[$addToSumId] = 0.00;
      $ctrl["_render"]->addToSumValue[$addToSumId] += (float) $value;
    }
  }

  if (!$noForm && $ctrl["readonly"]) {
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
    echo " value=\"{$tPattern}\"";
    echo '>';
    $noForm = true;
  }
  if (isset($layout["printSum"])) { # filter based on [data-printSum~={$printSumId}]
    $noForm = true;
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
    $fType = $layout["type"];
    if ($fType == "iban")
      $fType = "text";
    if ($fType == "titelnr")
      $fType = "text";
    echo "<input class=\"form-control\" type=\"{$fType}\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
  }

  if (isset($layout["addToSum"])) { # filter based on [data-addToSum~={$addToSumId}]
    echo " data-addToSum=\"".htmlspecialchars(implode(" ", $layout["addToSum"]))."\"";
  }
  if (isset($layout["printSum"])) { # filter based on [data-printSum~={$printSumId}]
    echo " data-printSum=\"".htmlspecialchars(implode(" ", $layout["printSum"]))."\"";
  }
  if ($layout["type"] == "iban") {
    echo " data-validateIBAN=\"1\"";
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
    else if (isset($layout["pattern-from-prefix"])) {
      $pattern = hexEscape($layout["pattern-from-prefix"]).".*"; # preg_quote produces invalid \: result
      echo " pattern=\"".htmlspecialchars($pattern)."\"";
      echo " data-pattern-from-prefix=\"".htmlspecialchars($layout["pattern-from-prefix"])."\"";
    }
    if (isset($layout["pattern-error"]))
      echo " data-pattern-error=\"".htmlspecialchars($layout["pattern-error"])."\"";
    if ($layout["type"] == "email") {
      echo " data-remote=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=validate.email&nonce=".urlencode($nonce))."\"";
      echo " data-remote-error=\"Ungültige eMail-Adresse\"";
    } elseif ($layout["type"] == "url" && in_array("wikiUrl", $layout["opts"])) {
      echo " data-tree-url=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=propose.wiki&nonce=".urlencode($nonce))."\"";
      echo " data-remote=\"".htmlspecialchars(str_replace("//","/",$URIBASE."/")."validate.php?ajax=1&action=validate.wiki&nonce=".urlencode($nonce))."\"";
    }
    if (isset($layout["onClickFillFrom"]))
      echo " data-onClickFillFrom=\"".htmlspecialchars($layout["onClickFillFrom"])."\"";
    if (isset($layout["onClickFillFromPattern"]))
      echo " data-onClickFillFromPattern=\"".htmlspecialchars($layout["onClickFillFromPattern"])."\"";
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
     <ul id="<?php echo htmlspecialchars($dsId); ?>" class="dropdown-menu" role="menu">
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
     <span></span> <!-- // for borders -->
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
    if ($layout["type"] == "url" && in_array("wikiUrl", $layout["opts"]))
      echo '<div class="tree-view" aria-hidden="true" id="'.htmlspecialchars($ctrl["id"]).'-treeview"></div>';
  }
}

function renderFormItemMoney($layout, $ctrl) {
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $value = "0.00";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  } elseif (isset($layout["value"])) {
    $value = $layout["value"];
  }
  $fvalue = convertDBValueToUserValue($value, $layout["type"]);
  $tPattern = newTemplatePattern($ctrl, htmlspecialchars($fvalue));

  $ctrl["_render"]->displayValue = htmlspecialchars($value);
  if (isset($layout["addToSum"])) {
    foreach ($layout["addToSum"] as $addToSumId) {
      $ctrl["_render"]->addToSumMeta[$addToSumId] = $layout;
      if (!isset($ctrl["_render"]->addToSumValue[$addToSumId]))
        $ctrl["_render"]->addToSumValue[$addToSumId] = 0.00;
      $ctrl["_render"]->addToSumValue[$addToSumId] += (float) $value;
    }
  }

  if (!$noForm && $ctrl["readonly"]) {
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
    echo " value=\"{$tPattern}\"";
    echo '>';
    $noForm = true;
  }

  if (isset($layout["printSum"])) { # filter based on [data-printSum~={$printSumId}]
    $noForm = true;
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
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  } elseif (isset($layout["value"])) {
    $value = $layout["value"];
  }

  $ctrl["_render"]->displayValue = htmlspecialchars($value);

  if (!$noForm && $ctrl["readonly"]) {
    $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));
    echo "<textarea style=\"display:none;\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\">";
    echo $tPattern;
    echo '</textarea>';
    $noForm = true;
  }

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
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

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
      if (!$ctrl["readonly"]) {
        echo "<a href=\"#\" class=\"on-click-rename-file\"><i class=\"fa fa-fw fa-pencil\"></i></a>";
        echo "<a href=\"#\" class=\"on-click-delete-file\"><i class=\"fa fa-fw fa-trash\"></i></a>";
      }
      echo "<input type=\"hidden\" name=\"".htmlspecialchars($renameFileFieldName)."\" orig-name=\"".htmlspecialchars($renameFileFieldNameOrig)."\" id=\"".htmlspecialchars($ctrl["id"])."-newFileName\" value=\"\" class=\"form-file-name\"/>";
      echo $oldFieldName;
      echo "</div>";
    } elseif ($ctrl["readonly"]) {
      echo "<div class=\"single-file-container\">";
      echo "</div>";
    } else {
      echo $myOut;
    }
  }
}

function renderFormItemMultiFile($layout, $ctrl) {
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  if ($noForm && isset($layout["destination"]))
    return false; // no data here

  if (!$noForm && $ctrl["readonly"] && isset($layout["destination"]))
    return false;

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
      if (!$ctrl["readonly"]) {
        echo "<a href=\"#\" class=\"on-click-rename-file\"><i class=\"fa fa-fw fa-pencil\"></i></a>";
        echo "<a href=\"#\" class=\"on-click-delete-file\"><i class=\"fa fa-fw fa-trash\"></i></a>";
      }
      echo "<input type=\"hidden\" name=\"".htmlspecialchars($renameFileFieldName)."\" orig-name=\"".htmlspecialchars($renameFileFieldNameOrig)."\" id=\"".htmlspecialchars($ctrl["id"])."-newFileName\" value=\"\" class=\"form-file-name\"/>";
      echo $oldFieldName;
      echo "</li>";
    }
    echo "</ul>";
  }

  if (!$ctrl["readonly"]) {
    echo "<input class=\"form-control multi-file\" type=\"file\" name=\"".htmlspecialchars($ctrl["name"])."[]\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\"[] id=\"".htmlspecialchars($ctrl["id"])."\" multiple";
    if (in_array("dir", $layout["opts"])) {
      echo " webkitdirectory";
    }
    echo "/>";
  }
  echo "</div>";
}

function getTrText($trId, $ctrl) {
  $matches = [];
  $origValue = $trId;

  if ($trId == "")
    return "";

  if (!preg_match('/^(.*)\{([0-9\-]+)\}$/', $trId, $matches)) {
    return newTemplatePattern($ctrl, htmlspecialchars("invalid row id: ".$trId));
  }

  $tableBaseName = $matches[1];
  $rowIdentifier = $matches[2];
  // rowIdentifier is stored in $tableBaseName[rowId]$suffix

  if (isset($ctrl["_values"])) {
    $ret = getFormEntries("{$tableBaseName}[rowId]", "table", $ctrl["_values"]["_inhalt"], $rowIdentifier);
    if (count($ret) == 0) {
      return newTemplatePattern($ctrl, htmlspecialchars("unknown row id: ".$trId));
    }
    if (count($ret) > 1) {
      return newTemplatePattern($ctrl, htmlspecialchars("non-unique row id: ".$trId));
    }
    $trName = str_replace("[rowId]", "", $ret[0]["fieldname"]);
  } else {
    return newTemplatePattern($ctrl, htmlspecialchars("missing formdata to resolve row id: ".$trId));
  }

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

  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  }
  if ($layout["type"] == "ref" && is_array($layout["references"]) && isset($layout["refValueIfEmpty"]) && $value == "" && isset($ctrl["_values"]) && isset($ctrl["_values"]["_inhalt"])) {
    $fvalue = getFormValueInt($layout["refValueIfEmpty"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  } else {
    $fvalue = $value;
  }
  if ($layout["type"] == "ref") {
    $rowId = false;
    if (is_array($layout["references"])) {
      if (isset($layout["referencesId"])) {
        $otherFormIdField = "formdata[{$layout["referencesId"]}]";
        /* rationale:otherFormIdField uses no suffix as 
         * 1. current logic ensures it always references the same form on every copy
         * 2. it would make checking references more difficult
         */
        $otherFormId = "";
        if (isset($ctrl["_values"]))
          $otherFormId = getFormValue($otherFormIdField, "otherForm", $ctrl["_values"]["_inhalt"], $otherFormId);
        if ($otherFormId != "")
          $layout["references"][0] = "id:{$otherFormId}";
      }
      $tmp = otherForm($layout, $ctrl);
      $txtTr = "";
      if ($tmp !== false) {
        $otherForm = $tmp["form"];
        $otherCtrl = $tmp["ctrl"];
        $otherAntrag = $tmp["antrag"];
  
        $rowId = false;
        if (isset($layout["referencesKey"])) {
          $rowIdentifier = false;
          foreach (array_keys($layout["referencesKey"]) as $tableName) {
            $ret = getFormEntries($layout["referencesKey"][$tableName], null, $otherCtrl["_values"]["_inhalt"], $fvalue);
            if (count($ret) != 1)
              continue;
            $suffix = substr($ret[0]["fieldname"], strlen($layout["referencesKey"][$tableName]));
            $rowIdentifier = getFormValueInt("{$tableName}[rowId]{$suffix}", null, $otherCtrl["_values"]["_inhalt"], false);
            if ($rowIdentifier === false)
              continue;
            $rowId = "{$tableName}{{$rowIdentifier}}";
            break;
          }
        } else if ($fvalue != "") {
          $rowId = $fvalue;
        }
      }
    } else if ($fvalue != "") {
      $rowId = $fvalue;
    }
    if ($rowId !== false && $rowId != "" && !in_array("no-invref", $layout["opts"]) ) {
      $ctrl["_render"]->referencedBy[$rowId][] = $ctrl["_render"]->currentParent."[".$ctrl["_render"]->currentParentRow."]";
    }
  }

  if (!$noForm && $ctrl["readonly"]) {
    $tPattern =  newTemplatePattern($ctrl, htmlspecialchars($value));
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
    echo " value=\"{$tPattern}\"";
    echo '>';
    $noForm = true;
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
    } else if ($layout["type"] == "ref" && is_array($layout["references"])) {
      if ($rowId === false || $rowId == "") {
        $txtTr = htmlspecialchars($value);
      } else {
        $txtTr = getTrText($rowId, $otherCtrl);
        $txtTr = processTemplates($txtTr, $otherCtrl); // rowTxt is from displayValue and thus already escaped
      }

      $tPattern = newTemplatePattern($ctrl, $txtTr);
      echo "<div>";
      echo $tPattern;
      echo "</div>";
    } else if ($layout["type"] == "ref") {
      $tPattern = newTemplatePattern($ctrl, htmlspecialchars("<{ref:$value}>"));
      echo "<div>";
      echo $tPattern;
      echo "</div>";
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
  if ($layout["type"] == "ref" && is_string($layout["references"])) {
    $layout["references"] = str_replace(".", "-", $layout["references"]);
    echo " data-references=\"".htmlspecialchars($layout["references"])."\"";
  }
  if ($layout["type"] == "ref" && is_array($layout["references"]) && isset($layout["updateByReference"])) {
    echo " data-update-value-maps=\"present\"";
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
  if ($layout["type"] == "ref" && is_array($layout["references"])) {
    list ($txt, $otherFormId) = otherFormTrOptions($layout, $ctrl);
    echo $txt;
  }

  echo "</select>";
  if ($layout["type"] == "ref" && is_array($layout["references"]) && isset($layout["referencesId"])) {
    $otherFormIdField = "formdata[{$layout["referencesId"]}]";
    $otherFormIdTypeField = "formtype[{$layout["referencesId"]}]";
    /* rationale:otherFormIdField uses no suffix as 
     * 1. current logic ensures it always references the same form on every copy
     * 2. it would make checking references more difficult
     */
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($otherFormIdField)."\" value=\"".htmlspecialchars($otherFormId)."\">";
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($otherFormIdTypeField)."\" value=\"otherForm\">";
  }
  echo "</div>";
}

function otherForm(&$layout, &$ctrl) {
  $fieldValue = false;
  $fieldName = false;
  if (is_array($layout["references"])) {
    $formFilterDef = $layout["references"][0];
    $f = ["type" => $formFilterDef["type"]];
    if (isset($formFilterDef["state"]))
      $f["state"] = $formFilterDef["state"];
    if (isset($formFilterDef["revision"]))
      $f["revision"] = $formFilterDef["revision"];
    $al = dbFetchAll("antrag", $f);
    $currentFormId = false;
    if (isset($ctrl["_values"])) {
      $currentFormId = $ctrl["_values"]["id"];
    }
    $fieldValue = [];

    foreach ($al as $a) {
      if (isset($formFilterDef["referenceFormField"])) {
        $r = dbGet("inhalt", ["antrag_id" => $a["id"], "fieldname" => $formFilterDef["referenceFormField"], "contenttype" => "otherForm" ]);
        if ($r === false || $r["value"] != $currentFormId) continue;
      }
      $fieldValue[] = $a["id"];
    }
    if (count($fieldValue) != 1)
      $fieldValue = false;
    else
      $fieldValue = $fieldValue[0];
  } elseif ($layout["references"][0] == "referenceField") {
    if (!isset($ctrl["_config"]["referenceField"])) {
      return false; #no such field
    }
    $fieldName = $ctrl["_config"]["referenceField"]["name"];
  } elseif (substr($layout["references"][0],0,6) == "field:") {
    $fieldName = substr($layout["references"][0],6);
  } elseif (substr($layout["references"][0],0,3) == "id:") {
    $fieldValue = substr($layout["references"][0],3);
  } else {
    die("Unknown otherForm reference in references: {$layout["references"][0]}");
  }
  if ($fieldValue === false && $fieldName !== false && isset($ctrl["_values"]) && isset($ctrl["_values"]["_inhalt"]))
    $fieldValue = getFormValueInt($fieldName, null, $ctrl["_values"]["_inhalt"], $fieldValue);
  if ($fieldValue === false || $fieldValue == "") {
    return false; # nothing given here
  }
  $fieldValue = (int) $fieldValue;

  if (!isset($ctrl["_render"]->otherForm[$fieldValue])) {
    $otherAntrag = getAntrag($fieldValue);
    if ($otherAntrag === false) return ""; # not readable. Ups.
    $otherForm =  getForm($otherAntrag["type"], $otherAntrag["revision"]);
    $otherCtrl = ["_values" => $otherAntrag, "render" => ["no-form"]];

    ob_start();
    renderFormImpl($otherForm, $otherCtrl);
    ob_end_clean();

    $ctrl["_render"]->otherForm[$fieldValue] = ["form" => $otherForm, "ctrl" => $otherCtrl, "antrag" => $otherAntrag];
  } else {
    $otherForm = $ctrl["_render"]->otherForm[$fieldValue]["form"];
    $otherCtrl = $ctrl["_render"]->otherForm[$fieldValue]["ctrl"];
    $otherAntrag = $ctrl["_render"]->otherForm[$fieldValue]["antrag"];
  }

  return ["form" => $otherForm, "ctrl" => $otherCtrl, "antrag" => $otherAntrag ];
}

function otherFormTrOptions($layout, $ctrl) {
  $tmp = otherForm($layout, $ctrl);
  if ($tmp === false) return "";
  $otherForm = $tmp["form"];
  $otherCtrl = $tmp["ctrl"];
  $otherAntrag = $tmp["antrag"];

  $tableNames = $layout["references"][1];
  if (!isset($otherCtrl["_render"])) {
    return "Rendering skipped due to nesting";
  }

  if (!is_array($tableNames)) $tableNames = [ $tableNames => $tableNames ];
  $ret = "";

  foreach ($tableNames as $tableName => $label) {
    if (!isset($otherCtrl["_render"]->numTableRows[$tableName]))
      continue;

    if (count($tableNames) > 1) {
      $ret .= "<optgroup label=\"".htmlspecialchars($label)."\">";
    }
  
    foreach ($otherCtrl["_render"]->numTableRows[$tableName] as $suffix => $rowCount) {
     $ret .= "\n<!-- row count $tableName : $rowCount -->";
      for($i=0; $i < $rowCount; $i++) {
        if (!isset($otherCtrl["_values"])) {
          $rowId = false;
          $rowKey = false;
        } else {
          $rowId = getFormValueInt("{$tableName}[rowId]{$suffix}[{$i}]", null, $otherCtrl["_values"]["_inhalt"], false);
          if (isset($layout["referencesKey"]) && isset($layout["referencesKey"][$tableName]))
            $rowKey = getFormValueInt("{$layout["referencesKey"][$tableName]}{$suffix}[{$i}]", null, $otherCtrl["_values"]["_inhalt"], false);
          else
            $rowKey = "{$tableName}{{$rowId}}";
        }
        if ($rowId !== false) {
          $txtTr = getTrText("{$tableName}{{$rowId}}", $otherCtrl);
          $txtTr = processTemplates($txtTr, $otherCtrl); // rowTxt is from displayValue and thus already escaped ;; pattern stored in otherRenderer thus copy
        } else {
          $txtTr = "missing {$tableName}[rowId]{$suffix}[{$i}]";
        }
        $tPattern = newTemplatePattern($ctrl, $txtTr);
  
        $updateByReference = [];
        if (isset($layout["updateByReference"]))
          $updateByReference = $layout["updateByReference"];
        $updateValueMap = [];
        foreach ($updateByReference as $destFieldName => $sources) {
          /* we only care for destFieldName with same suffix */
          $destFieldNameOrig = $destFieldName;
          foreach($ctrl["suffix"] as $s) {
            $destFieldName .= "[{$s}]";
            $destFieldNameOrig .= "[]";
          }
          $otherFormFieldValue = "";
          foreach ($sources as $srcFieldId) {
            $currSuffix = "{$suffix}[{$i}]";
            while ($currSuffix !== false) {
              $srcFieldName = $srcFieldId . $currSuffix;
  
              $m = [];
              if (!preg_match('/(.*)(\[[^[]]*\])$/', $currSuffix, $m))
                $currSuffix = false;
              else
                $currSuffix = $m[1];
  
              $fieldValue = getFormValueInt($srcFieldName, null, $otherAntrag["_inhalt"], false);
              if ($fieldValue === false) continue; /* other form does not have this field */
              if ($fieldValue == "") continue; /* other form left this field empty */
              /* if found */
               $otherFormFieldValue = $fieldValue;
              break 2; /* while currSuffix, foreach sources */
            }
          }
  
          $updateValueMap[ $destFieldNameOrig ] = $otherFormFieldValue;
        }
        $ret .= "<option value=\"".htmlspecialchars($rowKey)."\" data-update-value-map=\"".htmlspecialchars(json_encode($updateValueMap))."\">{$tPattern}</option>";
      }
    }

    if (count($tableNames) > 1) {
      $ret .= "</optgroup>";
    }

  }

  if ($otherAntrag !== false)
    $otherFormId = $otherAntrag["id"];
  else
    $otherFormId = false;

  return [ $ret, $otherFormId ];
}

function renderFormItemDateRange($layout, $ctrl) {
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $valueStart = "";
  $valueEnd = "";
  if (isset($ctrl["_values"])) {
    $valueStart = getFormValue($ctrl["name"]."[start]", $layout["type"], $ctrl["_values"]["_inhalt"], $valueStart);
    $valueEnd = getFormValue($ctrl["name"]."[end]", $layout["type"], $ctrl["_values"]["_inhalt"], $valueEnd);
  }
  $tPatternStart = newTemplatePattern($ctrl, htmlspecialchars($valueStart));
  $tPatternEnd =  newTemplatePattern($ctrl, htmlspecialchars($valueEnd));
  $ctrl["_render"]->displayValue = htmlspecialchars("$valueStart - $valueEnd");

  if (!$noForm && $ctrl["readonly"]) {
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."[start]\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."[start]\" value=\"{$tPatternStart}\">";
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."[end]\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."[end]\" value=\"{$tPatternEnd}\">";
    $noForm = true;
  }

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
          <input type="text"
                 class="input-sm form-control"
                 name="<?php echo htmlspecialchars($ctrl["name"]); ?>[start]"
                 orig-name="<?php echo htmlspecialchars($ctrl["orig-name"]); ?>[start]"
                 <?php echo (in_array("required", $layout["opts"]) ? "required=\"required\"": ""); ?>
                 <?php echo ($ctrl["readonly"] ? "readonly=\"readonly\"": ""); ?>
                 value="<?php echo $tPatternStart; ?>"
          />
          <div class="input-group-addon">
            <span class="glyphicon glyphicon-th"></span>
          </div>
        </div>
        <div class="input-group-addon" style="background-color: transparent; border: none;">
          bis
        </div>
        <div class="input-group">
          <input type="text"
                 class="input-sm form-control"
                 name="<?php echo htmlspecialchars($ctrl["name"]); ?>[end]"
                 orig-name="<?php echo htmlspecialchars($ctrl["orig-name"]); ?>[end]"
                 <?php echo (in_array("required", $layout["opts"]) ? "required=\"required\"": ""); ?>
                 <?php echo ($ctrl["readonly"] ? "readonly=\"readonly\"": ""); ?>
                 value="<?php echo $tPatternEnd; ?>"
          />
          <div class="input-group-addon">
            <span class="glyphicon glyphicon-th"></span>
          </div>
        </div>
    </div>
<?php

}


function renderFormItemDate($layout, $ctrl) {
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $value = "";
  if (isset($ctrl["_values"])) {
    $value = getFormValue($ctrl["name"], $layout["type"], $ctrl["_values"]["_inhalt"], $value);
  }
  $tPattern = newTemplatePattern($ctrl, htmlspecialchars($value));
  $ctrl["_render"]->displayValue = htmlspecialchars($value);

  if (!$noForm && $ctrl["readonly"]) {
    echo "<input type=\"hidden\" name=\"".htmlspecialchars($ctrl["name"])."\" orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\" id=\"".htmlspecialchars($ctrl["id"])."\"";
    echo " value=\"{$tPattern}\"";
    echo '>';
    $noForm = true;
  }

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
    <input type="text"
           class="form-control"
           name="<?php echo htmlspecialchars($ctrl["name"]); ?>"
           orig-name="<?php echo htmlspecialchars($ctrl["orig-name"]); ?>"
           id="<?php echo htmlspecialchars($ctrl["id"]); ?>"
           <?php echo (in_array("required", $layout["opts"]) ? "required=\"required\"": ""); ?>
           <?php echo (in_array("readonly", $layout["opts"]) ? "readonly=\"readonly\"": ""); ?>
           value="<?php echo $tPattern; ?>"
<?php
    if (isset($layout["onClickFillFrom"]))
      echo " data-onClickFillFrom=\"".htmlspecialchars($layout["onClickFillFrom"])."\"";
    if (isset($layout["onClickFillFromPattern"]))
      echo " data-onClickFillFromPattern=\"".htmlspecialchars($layout["onClickFillFromPattern"])."\"";
?>
    />
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
  $withHeadline = in_array("with-headline", $layout["opts"]);
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $cls = ["table", "table-striped", "summing-table"];
  if (!$noForm)
    $cls[] = "dynamic-table";
  if (in_array("fixed-width-table", $layout["opts"]))
    $cls[] = "fixed-width-table";
  if ($ctrl["readonly"] || $noForm)
    $cls[] = "dynamic-table-readonly";

  $rowCountFieldName =  "formdata[{$layout["id"]}][rowCount]";
  $rowCountFieldNameOrig = $rowCountFieldName;
  $rowCountFieldTypeName = "formtype[{$layout["id"]}]";
  $extraColsFieldName =  "formdata[{$layout["id"]}][extraCols]";
  $extraColsFieldNameOrig = $extraColsFieldName;
  $extraColsFieldTypeName = "formtype[{$layout["id"]}]";
  $rowIdCountFieldName =  "formdata[{$layout["id"]}][rowIdCount]";
  $rowIdCountFieldNameOrig = $rowIdCountFieldName;
  $rowIdCountFieldTypeName = "formtype[{$layout["id"]}]";
  $rowIdFieldName =  "formdata[{$layout["id"]}][rowId]";
  $rowIdFieldNameOrig = $rowIdFieldName;
  $rowIdFieldTypeName = "formtype[{$layout["id"]}]";
  foreach($ctrl["suffix"] as $suffix) {
    $rowCountFieldName .= "[{$suffix}]";
    $rowCountFieldNameOrig .= "[]";
    $extraColsFieldName .= "[{$suffix}]";
    $extraColsFieldNameOrig .= "[]";
    $rowIdCountFieldName .= "[{$suffix}]";
    $rowIdCountFieldNameOrig .= "[]";
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
  list ($a, $b) = getFormNames($ctrl["name"]);
  $ctrl["_render"]->numTableRows[$a][$b] = $rowCount;

  $rowIdCount = 0;
  if (isset($ctrl["_values"])) {
    $rowIdCount = (int) getFormValue($rowIdCountFieldName, $layout["type"], $ctrl["_values"]["_inhalt"], $rowIdCount);
  }

?>

  <table class="<?php echo implode(" ", $cls); ?>" id="<?php echo htmlspecialchars($ctrl["id"]); ?>" orig-id="<?php echo htmlspecialchars($ctrl["orig-id"]); ?>" name="<?php echo htmlspecialchars($ctrl["name"]); ?>" orig-name="<?php echo htmlspecialchars($ctrl["orig-name"]); ?>">

<?php
  if (!$noForm) {
    echo "<input type=\"hidden\" value=\"".htmlspecialchars($rowCount)."\" name=\"".htmlspecialchars($rowCountFieldName)."\" orig-name=\"".htmlspecialchars($rowCountFieldNameOrig)."\" class=\"store-row-count\"/>";
    echo "<input type=\"hidden\" value=\"".htmlspecialchars($layout["type"])."\" name=\"".htmlspecialchars($rowCountFieldTypeName)."\"/>";
    echo "<input type=\"hidden\" value=\"".htmlspecialchars($rowIdCount)."\" name=\"".htmlspecialchars($rowIdCountFieldName)."\" orig-name=\"".htmlspecialchars($rowIdCountFieldNameOrig)."\" class=\"store-row-id-count\"/>";
    echo "<input type=\"hidden\" value=\"".htmlspecialchars($layout["type"])."\" name=\"".htmlspecialchars($rowIdCountFieldTypeName)."\"/>";
  }

  $compressableColumns = [];
  foreach ($layout["columns"] as $i => $col) {
    $layout["columns"][$i]["_hideable_isHidden"] = false;
    if (!isset($col["opts"]) || !in_array("hideable", $col["opts"]))
      continue;
    $name = "[$i]";
    if (isset($col["name"]))
      $name = $col["name"];
    $colId = $col["id"];
    $fname = $extraColsFieldName . "[" . $colId . "]";
    $fnameOrig = $extraColsFieldNameOrig . "[" . $colId . "]";
    if (isset($ctrl["_values"])) {
      $value = getFormValue($fname, null, $ctrl["_values"]["_inhalt"], ""); # checkbox does not store value if unchecked
    } else {
      $value = "show"; # default to show
    }
    $isChecked = ($value == "show");
    $compressableColumns[] = ["name" => $name, "i" => $i, "fname" => $fname, "fnameOrig" => $fnameOrig, "isChecked" => $isChecked ];
    $layout["columns"][$i]["_hideable_isHidden"] = !$isChecked;
  }

  $withHeadlineRow = $withHeadline || (count($compressableColumns) > 0);

  if ($withHeadlineRow) {

?>

    <thead>
      <tr>
<?php
        $colSpan = 0;
        if (!$noForm)
          $colSpan++; # delete-row
        if ($withRowNumber)
          $colSpan++;
        if ($colSpan > 0)
          echo "<th colspan=\"{$colSpan}\">";
        if (count($compressableColumns) > 0 && !$noForm) {
          echo "<input type=\"hidden\" value=\"".htmlspecialchars($layout["type"])."\" name=\"".htmlspecialchars($extraColsFieldTypeName)."\"/>";
?>
          <div class="dropdown">
            <button type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
<?php

    foreach ($compressableColumns as $m) {
      $i = $m["i"];
      $name = $m["name"];
      $fname = $m["fname"];
      $fnameOrig = $m["fnameOrig"];
      $isChecked = $m["isChecked"];
?>
              <li><a href="javascript:void(false);" class="toggle-checkbox">
                  <input type="checkbox"
                         name="<?php echo htmlspecialchars($fname); ?>"
                         orig-name="<?php echo htmlspecialchars($fnameOrig); ?>"
                         <?php if ($isChecked) echo "checked=\"checked\""; ?>
                         data-col-class="dynamic-table-col-<?php echo htmlspecialchars($i); ?>"
                         class="col-toggle"
                         value="show" >
                  <?php echo htmlspecialchars($name); ?>
                  </a>
              </li>
<?php
    }

?>
            </ul>
          </div>
<?php
        }
        echo "</th>";
        foreach ($layout["columns"] as $i => $col) {
          $cls = [ "dynamic-table-cell", "dynamic-table-col-$i" ];
          if ($layout["columns"][$i]["_hideable_isHidden"])
            $cls[] = "hide-column-manual";
          echo "<th class=\"".implode(" ", $cls)."\">";
          if ($withHeadline) {
            if ($col["name"] === true) {
              if ($col["type"] == "group") {
                $colWidthSum = 0;
                foreach ($col["children"] as $child) {
                  $title = (isset($child["title"]) ? $child["title"] : ( isset($child["name"]) ? $child["name"] : "{$child["id"]}") );
                  if (isset($child["width"])) {
                    $colWidthSum += $child["width"];
                    echo "<span class=\"dynamic-table-caption col-xs-{$child["width"]}\">".htmlspecialchars($title)."</span>";
                  } else {
                    $colWidthSum += 1;
                    echo "<span class=\"dynamic-table-caption\">".htmlspecialchars($title)."</span>";
                  }
                  if ($colWidthSum >= 12) break;
                }
              } elseif( isset ($col["title"])) {
                echo "<span class=\"dynamic-table-caption\">".htmlspecialchars($col["title"])."</span>";
              }
            } else {
              echo "<span class=\"dynamic-table-caption\">".htmlspecialchars($col["name"])."</span>";
            }
          }
          echo "</th>";
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
       if ($rowNumber == $rowCount)
         $thisSuffix = false;
       else
         $thisSuffix = $rowNumber;
       $newSuffix = $ctrl["suffix"];
       $newSuffix[] = $thisSuffix;
       $ctrl["_render"]->displayValue = false;
       $ctrl["_render"]->currentParentRow = $rowNumber;
       $addToSumValueBeforeRow = $ctrl["_render"]->addToSumValue;
       $rowTxt = [];

       $myRowIdFieldName = $rowIdFieldName;
       $myRowIdFieldNameOrig = $rowIdFieldNameOrig;
       foreach($newSuffix as $suffix) {
         $myRowIdFieldName .= "[{$suffix}]";
         $myRowIdFieldNameOrig .= "[]";
       }
       $myRowId = $rowIdCount;
       if (isset($ctrl["_values"])) {
         $myRowId = getFormValue($myRowIdFieldName, $layout["type"], $ctrl["_values"]["_inhalt"], $myRowId);
       }
       $lastRowId = $ctrl["_render"]->currentRowId;
       $ctrl["_render"]->currentRowId = getBaseName($ctrl["_render"]->currentParent)."{".$myRowId."}";
       $ctrl["_render"]->rowIdToNumber[ $ctrl["_render"]->currentRowId ] = $ctrl["_render"]->currentParent."[".$rowNumber."]";
       $ctrl["_render"]->rowNumberToId[ $ctrl["_render"]->currentParent."[".$rowNumber."]" ] = $ctrl["_render"]->currentRowId;
?>
       <tr class="<?php echo implode(" ", $cls); ?>">
<?php

        if (!$noForm) {
          echo "<input type=\"hidden\" value=\"".htmlspecialchars($myRowId)."\" name=\"".htmlspecialchars($myRowIdFieldName)."\" orig-name=\"".htmlspecialchars($myRowIdFieldNameOrig)."\" class=\"store-row-id\"/>";
          echo "<input type=\"hidden\" value=\"".htmlspecialchars($layout["type"])."\" name=\"".htmlspecialchars($rowIdFieldTypeName)."\"/>";
        }

        if ($withRowNumber)
          echo "<td class=\"row-number\">".($rowNumber+1)."</td>";

        if (!$noForm) {
          echo "<td class=\"delete-row\">";
          echo "<a href=\"\" class=\"delete-row\"><i class=\"fa fa-fw fa-trash\"></i></a>";
          echo "</td>";
        }

        foreach ($layout["columns"] as $i => $col) {
          if (!isset($col["opts"]))
            $col["opts"] = [];

          $tdClass = [ "{$ctrl["id"]}-col-$i" ];
          if (in_array("title", $col["opts"]))
            $tdClass[] = "dynamic-table-column-title";
          else
            $tdClass[] = "dynamic-table-column-no-title";
          $tdClass[] = "dynamic-table-cell";
          $tdClass[] = "dynamic-table-col-$i";
          if ($layout["columns"][$i]["_hideable_isHidden"])
            $tdClass[] = "hide-column-manual";

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
        $ctrl["_render"]->currentRowId = $lastRowId;

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
        $colSpan = 0;
        if (!$noForm)
          $colSpan++; # delete-row
        if ($withRowNumber)
          $colSpan++;
        if ($colSpan > 0)
          echo "<th colspan=\"{$colSpan}\">";

        foreach ($layout["columns"] as $i => $col) {
          if (!isset($col["opts"])) $col["opts"] = [];
          if (in_array("sum-over-table-bottom", $col["opts"])) {
            $col["printSumFooter"][] = "col-sum-".$layout["id"]."-".$i;
          }
          $cls = [ "dynamic-table-cell", "dynamic-table-col-$i" ];
          if ($layout["columns"][$i]["_hideable_isHidden"])
            $cls[] = "hide-column-manual";
          if (isset($col["printSumFooter"]) && count($col["printSumFooter"]) > 0) {
            $cls[] = "cell-has-printSum";
            echo "<th class=\"".implode(" ", $cls)."\">";
            foreach ($col["printSumFooter"] as $psId) {
              if (isset($ctrl["_render"]->addToSumMeta[$psId])) {
                $newMeta = $ctrl["_render"]->addToSumMeta[$psId];
              } else {
                $newMeta = $col;
              }
              unset($newMeta["addToSum"]);
              if (isset($newMeta["width"]))
                unset($newMeta["width"]);
              if (isset($addToSumDifference[$psId]))
                $value = $addToSumDifference[$psId];
              else
                $value = 0.00;
              $value = number_format($value, 2, ".", "");
              $newMeta["value"] = $value;
              $newMeta["opts"][] = "is-sum";
              $newMeta["printSum"] = [ $psId ];
              if (count($col["printSumFooter"]) > 1 && isset($newMeta["name"]) && !isset($newMeta["title"])) {
                $newMeta["title"] = $newMeta["name"];
              }

              $newCtrl = $ctrl;
              $newCtrl["suffix"][] = "print-foot";
              $newCtrl["suffix"][] = $layout["id"];
              $newCtrl["render"][] = "no-form";
              unset($newCtrl["_values"]);
              renderFormItem($newMeta, $newCtrl);
            }
          } else {
            echo "<th class=\"".implode(" ", $cls)."\">";
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
<?php
  $ctrl["_render"]->displayValue = false;
  $ctrl["_render"]->currentParent = $myParent;
  $ctrl["_render"]->currentParentRow = $myParentRow;

}

function renderFormItemInvRef($layout,$ctrl) {
  list ($noForm, $noFormMarkup) = isNoForm($layout, $ctrl);

  $refId = $ctrl["_render"]->currentRowId;
  if ($refId === false) return false;

  $tPattern = newTemplatePattern($ctrl, htmlspecialchars("<{invref:".uniqid().":".$refId."}>"));
  echo $tPattern;
  $ctrl["_render"]->templates[$tPattern] = htmlspecialchars("{".$tPattern."}"); // fallback
  $ctrl["_render"]->postHooks[] = function($ctrl) use ($tPattern, $layout, $refId, $ctrl, $noForm) {
    global $URIBASE;

    $withHeadline = in_array("with-headline", $layout["opts"]);
    $withAggByForm = in_array("aggregate-by-otherForm", $layout["opts"]);
    if (isset($layout["printSum"]))
      $printSum = $layout["printSum"];
    else
      $printSum = [];
    $hasForms = isset($layout["otherForms"]);
    $currentFormId = false;
    if (isset($ctrl["_values"])) {
      $currentFormId = $ctrl["_values"]["id"];
    }

    $refMe = [];
    if ($noForm && isset($ctrl["_render"]->referencedBy[$refId])) {
      foreach( $ctrl["_render"]->referencedBy[$refId] as $r) {
        $refMe[-1][] = ["ctrl" => $ctrl, "ref" => $r ];
      }
    }
    if ($hasForms && $currentFormId !== false) {
      $forms = [];
      // find other forms
      foreach ($layout["otherForms"] as $formFilterDef) {
        $f = ["type" => $formFilterDef["type"]];
        if (isset($formFilterDef["state"]))
          $f["state"] = $formFilterDef["state"];
        $al = dbFetchAll("antrag", $f);
        foreach ($al as $a) {
          $r = dbGet("inhalt", ["antrag_id" => $a["id"], "fieldname" => $formFilterDef["referenceFormField"], "contenttype" => "otherForm" ]);
          if ($r === false || $r["value"] != $currentFormId) continue;
          $forms[$a["id"]] = ["antrag" => $a];
        }
      }
      foreach (array_keys($forms) as $aId) {
        $m = $forms[$aId];
        $a = $m["antrag"];
        $i = dbFetchAll("inhalt", ["antrag_id" => $a["id"]]);
        $a["_inhalt"] = $i;

        $f = getForm($a["type"], $a["revision"]);
        $readPermitted = hasPermission($f, $a, "canRead");

        if (!$readPermitted) {
          echo "<i>Formular nicht lesbar: ".newTemplatePattern($ctrl, htmlspecialchars($value))."</i>";
          unset($forms[$aId]);
          continue;
        }
        $otherCtrl = ["_values" => $a, "render" => ["no-form"]];

        ob_start();
        renderFormImpl($f, $otherCtrl);
        ob_end_clean();

        $m["form"] = $f;
        $m["ctrl"] = $otherCtrl;
        $m["antrag"] = $a;
        $forms[$aId] = $m;

        if (!isset($otherCtrl["_render"])) {
          echo "cannot identify references due to nesting";
          continue;
        }
        if (isset($otherCtrl["_render"]->referencedBy[$refId])) {
          foreach( $otherCtrl["_render"]->referencedBy[$refId] as $r) {
            $refMe[$aId][] = ["ctrl" => $otherCtrl, "ref" => $r, "form" => $f, "antrag" => $a ];
          }
        }
      }
    }

    $columnSum = [];
    $myOutBody = "";

    foreach ($refMe as $grp => $rr) {
      $otherFormSum = [];
      for ($i = count($rr) - 1; $i >= 0; $i--) {
        $r = $rr[$i];
        $refRow = $r["ref"];
        $refCtrl = $r["ctrl"];

        foreach ($printSum as $psId) {
          if ($refRow == "[]")
            $value = $refCtrl["_render"]->addToSumValue[$psId];
          else
            $value = $refCtrl["_render"]->addToSumValueByRowRecursive[$refRow][$psId];
          $value = number_format($value, 2, ".", "");
          if (!isset($columnSum[ $psId ]))
            $columnSum[ $psId ] = 0.00;
          $columnSum[ $psId ] += (float) $value;
          if (!isset($otherFormSum[ $psId ]))
            $otherFormSum[ $psId ] = 0.00;
          $otherFormSum[ $psId ] += (float) $value;

          if (isset($refCtrl["_render"]->addToSumMeta[$psId]) && !isset($ctrl["_render"]->addToSumMeta[$psId])) {
            $ctrl["_render"]->addToSumMeta[$psId] = $refCtrl["_render"]->addToSumMeta[$psId];
          }
        }

        if ($withAggByForm && $i > 0) continue; # not last

        $myOutBody .= "    <tr>\n";
        if ($hasForms) {
          $revConfig = getFormConfig($r["antrag"]["type"], $r["antrag"]["revision"]);
          $caption = getAntragDisplayTitle($r["antrag"], $revConfig);
          $caption = trim(implode(" ", $caption));
          $url = str_replace("//","/", $URIBASE."/".$r["antrag"]["token"]);
          $myOutBody .= "<td>[".$r["antrag"]["id"]."] <a href=\"".htmlspecialchars($url)."\">".$caption."</a></td>";
        }
        if (!$withAggByForm) {
          $refRowId = $ctrl["_render"]->rowNumberToId[$refRow];
          $txtTr = getTrText($refRowId, $refCtrl);
          $txtTr = newTemplatePattern($ctrl, processTemplates($txtTr, $refCtrl));
          $myOutBody .= "      <td class=\"invref-txtTr\">{$txtTr}</td>\n"; /* Spalte: Quelle */
        }
  
        foreach ($printSum as $psId) {
          if ($withAggByForm)
            $value = $otherFormSum[$psId];
          else
            if ($refRow == "[]")
              $value = $refCtrl["_render"]->addToSumValue[$psId];
            else
              $value = $refCtrl["_render"]->addToSumValueByRowRecursive[$refRow][$psId];
          $value = number_format($value, 2, ".", "");
          if (isset($refCtrl["_render"]->addToSumMeta[$psId])) {
            $newMeta = $refCtrl["_render"]->addToSumMeta[$psId];
            $newMeta["addToSum"] = [ "invref-".$layout["id"]."-".$psId ];
            $newMeta["printSum"] = [ $psId ];
            $newMeta["value"] = $value;
  
            $newCtrl = array_merge($refCtrl, ["wrapper"=> "td", "class" => [ "cell-has-printSum" ] ]);
            $newCtrl["suffix"][] = "print";
            $newCtrl["suffix"][] = $layout["id"];
            $newCtrl["render"][] = "no-form";
            unset($newCtrl["_values"]);
            ob_start();
            renderFormItem($newMeta, $newCtrl);
            $myOutBody .= newTemplatePattern($ctrl, processTemplates(ob_get_contents(), $newCtrl));
            ob_end_clean();
          } else {
            $myOutBody .= "    <td class=\"cell-has-printSum\">";
            $myOutBody .= "      <div data-printSum=\"".htmlspecialchars($psId)."\">".htmlspecialchars($value)."</div>";
            $myOutBody .= "    </td>\n";
          }
        }
        $myOutBody .= "    </tr>\n";
      }
    }
    if (!$noForm) {
      $myOutBody .= "    <tr class=\"invref-template summing-skip\">\n";
      if ($hasForms && !$withAggByForm) {
        $myOutBody .= "      <td></td>\n"; /* Spalte: Quelleformular */
      }
      $myOutBody .= "      <td class=\"invref-rowTxt\"></td>\n"; /* Spalte: Quelle */

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
          $myOutBody .= ob_get_contents();
          ob_end_clean();
        } else {
          $myOutBody .= "    <td class=\"cell-has-printSum\">";
            $myOutBody .= "    <div data-printSum=\"".htmlspecialchars($psId)."\">no meta data for ".htmlspecialchars($psId)."</div>";
          $myOutBody .= "    </td>\n";
        }
      }

      $myOutBody .= "    </tr>\n";
    }

    $myOutHead = "  <thead>\n";
    $myOutHead .= "    <tr>\n";
    if ($hasForms && !$withAggByForm) {
      $myOutHead .= "      <td></td>\n"; /* Spalte: Quelleformular */
    }
    $myOutHead .= "      <td></td>\n"; /* Spalte: Quelle */
    foreach ($printSum as $psId) {
      if (isset($ctrl["_render"]->addToSumMeta[$psId])) {
        $newMeta = $ctrl["_render"]->addToSumMeta[$psId];
        $title = $psId;
        if (isset($newMeta["name"])) $title = $newMeta["name"];
        if (isset($newMeta["name"])) $title = $newMeta["name"];
        if (isset($newMeta["title"])) $title = $newMeta["title"];
        $myOutHead .= "    <th>".htmlspecialchars($title)."</th>";
      } else {
        $myOutHead .= "    <th>".htmlspecialchars($psId)."</th>";
      }
    }

    $myOutHead .= "    </tr>\n";
    $myOutHead .= "  </thead>\n";

    $myOut = "<table class=\"table table-striped invref summing-table\" id=\"".htmlspecialchars($ctrl["id"])."\" name=\"".htmlspecialchars($ctrl["name"])."\"  orig-name=\"".htmlspecialchars($ctrl["orig-name"])."\">\n";
    if ($withHeadline) {
      $myOut .= $myOutHead;
    }
    $myOut .= "  <tbody>\n";
    $myOut .= $myOutBody;
    $myOut .= "  </tbody>\n";
    $myOut .= "  <tfoot>\n";
    $myOut .= "    <tr>\n";
    if ($hasForms && !$withAggByForm) {
      $myOut .= "      <td></td>\n"; /* Spalte: Quelleformular */
    }
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
