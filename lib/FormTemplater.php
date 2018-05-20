<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 28.04.18
 * Time: 21:40
 */

class FormTemplater{
    
    private static $ID_DELIMITER = "__";
    /**
     * @var $permissionHandler PermissionHandler
     */
    
    private $noValueStringInReadOnly;
    private $permissionHandler;
    
    /**
     * FormTemplater constructor.
     *
     * @param PermissionHandler $permissionHandler
     * @param string            $noValue
     */
    public function __construct(PermissionHandler $permissionHandler, string $noValue = "keine Angabe"){
        $this->permissionHandler = $permissionHandler;
        $this->noValueStringInReadOnly = $noValue;
    }
    
    static function generateTitelSelectable($hhp_id){
        $all_titels = DBConnector::getInstance()->dbFetchAll("haushaltsgruppen",
            ["haushaltsgruppen.id", "haushaltstitel.id", "gruppen_name", "titel_name", "titel_nr", "type"], ["haushaltsgruppen.hhp_id" => $hhp_id],
            [["type" => "left", "table" => "haushaltstitel", "on" => ["haushaltsgruppen.id", "haushaltstitel.hhpgruppen_id"]]],
            ["type" => true, "haushaltsgruppen.id" => true, "titel_nr" => true], true);
        $selectable = [];
        foreach ($all_titels as $g_id => $group){
            $ret_group = ["label" => ($group[0]["type"] ? "Ausgabe" : "Einnahme") . " - " . $group[0]["gruppen_name"]];
            foreach ($group as $titel){
                $option = [];
                $option["label"] = $titel["titel_name"];
                $option["subtext"] = $titel["titel_nr"];
                $option["value"] = $titel["id"];
                //set in parent
                $ret_group["options"][] = $option;
            }
            //set in parent
            $selectable["groups"][] = $ret_group;
        }
        return $selectable;
    }
    
    static function generateGremienSelectable($all = false){
        $GremiumPrefix = $GLOBALS["GremiumPrefix"];
        if ($all)
            $gremien = AuthHandler::getInstance()->getAttributes()["alle-gremien"];
        else
            $gremien = AuthHandler::getInstance()->getAttributes()["gremien"];
        sort($gremien);
        $selectable = [];
        
        foreach ($GremiumPrefix as $gremiumgroupname){
            $group = [];
            $group["label"] = $gremiumgroupname;
            
            $options = [];
            foreach ($gremien as $gremium){
                if (strpos($gremium, $gremiumgroupname) !== false)
                    $options[] = ["label" => $gremium];
            }
            $group["options"] = $options;
            $selectable["groups"][] = $group;
        }
        
        return $selectable;
    }
    
    static function generateSelectable($list){
        
        $selectable = [];
        $options = [];
        foreach ($list as $item){
            $options[] = ["label" => $item];
        }
        //only 1 group
        $selectable["groups"][0]["options"] = $options;
        
        return $selectable;
    }
    
    function getStateChooser(StateHandler $stateHandler){
        $out = "";
        $optionsNext = $stateHandler->getNextStates();
        //$optionsBack = $stateHandler->getStatesBefore();
        
        $out .= "<div>";
        //foreach ($optionsBack as $oldState){
        //    $out .= "<button class='btn btn-warning'>{$stateHandler->getFullStateNameFrom($oldState)}</button>";
        //}
        //echo "</div>";
        $out .= "<button class='btn btn-primary'>{$stateHandler->getFullStateName()}</button>";
        //echo "<div>";
        foreach ($optionsNext as $nextState){
            $out .= "<button class='btn btn-success'>{$stateHandler->getFullStateNameFrom($nextState)}</button>";
        }
        $out .= "</div>";
        return $out;
        
    }
    
    public function getCheckboxForms($name, $value = false, $width = 12, $label_text = "", $validator = []){
        $unique_id = htmlspecialchars($this->getUniqueIDfromName($name));
        $editable = $this->checkWritePermission($name);
        $out = "";
        if ($editable){
            $additonal_array = $this->constructValidatorStrings($validator);
            if ($value !== false)
                $additonal_array[] = "checked";
            $additonal_str = implode(" ", $additonal_array);
            $out .= "<div class='checkbox'>";
            $out .= "<label><input id='$unique_id' name='$name' type='checkbox' value='" . ($value !== false) . "' $additonal_str>$label_text</label>";
            $out .= "</div>";
        }else{
            if ($value !== false)
                $iconName = "fa-check-square";
            else
                $iconName = "fa-square-o";
            $out .= "<i class='fa fa-fw $iconName'></i>&nbsp;$label_text";
        }
        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, "", $validator);
    }
    
    private function getUniqueIDfromName($name){
        return htmlspecialchars(explode("[", $name)[0] . self::$ID_DELIMITER . uniqid());
    }
    
    private function checkWritePermission($name){
        if ($this->permissionHandler->checkWritePermission() === true)
            return true;
        return $this->permissionHandler->checkWritePermissionField($name);
    }
    
    private function constructValidatorStrings($validatorArray){
        if (!isset($validatorArray) || empty($validatorArray))
            return [];
        $ret = ["required"];
        
        if (isset($validatorArray["min-length"]))
            $ret[] = "data-minlength=" . $validatorArray["min-length"];
        if (isset($validatorArray["email"]))
            $ret[] = "data-remote='" . $GLOBALS['URIBASE'] . "validate.php?ajax=1&action=validate.email&nonce={$GLOBALS['nonce']}'";
        
        return $ret;
    }
    
    private function getReadOnlyValue($values){
        if (is_array($values)){
            return implode(",", array_map([$this, "getReadOnlyValue"], $values));
        }
        if (empty($values)){
            return "<i>" . htmlspecialchars($this->noValueStringInReadOnly) . "</i>";
        }else{
            return htmlspecialchars($values);
        }
    }
    
    private function getOutputWrapped($content, $width, $editable, $name, $unique_id, $label_text, $validator){
        $out = "";
        if ($this->checkVisibility($name) === false)
            return "";
        
        $classes_array = $this->constructWidthClasses($width);
        $classes_array[] = "form-group";
        if (!empty($validator) && $editable)
            $classes_array[] = "has-feedback";
        $classes_str = implode(" ", $classes_array);
        $out .= "<div class='$classes_str'>";
        if (!empty($label_text)){
            $out .= "<label class='control-label' for='$unique_id'>$label_text</label>";
        }
        $out .= $content;
        if (!empty($validator) && $editable){
            //$out .= "<span class='glyphicon form-control-feedback' aria-hidden='true'></span>";
            $out .= "<div class='help-block with-errors'></div>";
        }
        $out .= "</div>";
        
        return $out;
    }
    
    private function checkVisibility($name){
        return $this->permissionHandler->isVisibleField($name);
    }
    
    private function constructWidthClasses($width){
        if (!isset($width))
            return [];
        $base_cls = ["col-xs-", "col-xs-", "col-md-", "col-lg-"];
        $ret_cls = [];
        if (!is_array($width))
            $width = [$width];
        for ($i = 0; $i < count($width) && $i < 4; $i++){
            $ret_cls[] = $base_cls[$i] . $width[$i];
        }
        return $ret_cls;
    }
    
    function getFileForm($name, $value = "", $width = 12, $placeholder = "", $label_text = "", $validator = []){
        $unique_id = htmlspecialchars($this->getUniqueIDfromName($name));
        $editable = $this->checkWritePermission($name);
        $out = "";
        $out .= "<div class='single-file-container'>";
        $out .= "<input class='form-control single-file' type='file' name='$name' id='$unique_id'>";
        $out .= "</div>";
        /*
        $myOut = "<div class=\"single-file-container\">";
        $myOut .= "<input class=\"form-control single-file\" type=\"file\" name=\"" . htmlspecialchars($ctrl["name"]) . "\" orig-name=\"" . htmlspecialchars($ctrl["orig-name"]) . "\" id=\"" . htmlspecialchars($ctrl["id"]) . "\"/>";
        $myOut .= "</div>";
        if ($file){
            $renameFileFieldName = "formdata[{$layout["id"]}][newFileName]";
            $renameFileFieldNameOrig = $renameFileFieldName;
            foreach ($ctrl["suffix"] as $suffix){
                $renameFileFieldName .= "[{$suffix}]";
                $renameFileFieldNameOrig .= "[]";
            }
        
            echo "<div class=\"single-file-container\" data-display-text=\"" . newTemplatePattern($ctrl, $fileName) . "\" data-filename=\"" . newTemplatePattern($ctrl, $fileName) . "\" data-orig-filename=\"" . newTemplatePattern($ctrl, $fileName) . "\" data-old-html=\"" . htmlspecialchars($myOut) . "\">";
            echo "<span>" . $tPattern . "</span>";
            echo "<span>&nbsp;</span>";
            echo "<small><nobr class=\"show-file-size\">" . newTemplatePattern($ctrl, $file["size"]) . "</nobr></small>";
            if (!$ctrl["readonly"]){
                echo "<a href=\"#\" class=\"on-click-rename-file\"><i class=\"fa fa-fw fa-pencil\"></i></a>";
                echo "<a href=\"#\" class=\"on-click-delete-file\"><i class=\"fa fa-fw fa-trash\"></i></a>";
            }
            echo "<input type=\"hidden\" name=\"" . htmlspecialchars($renameFileFieldName) . "\" orig-name=\"" . htmlspecialchars($renameFileFieldNameOrig) . "\" id=\"" . htmlspecialchars($ctrl["id"]) . "-newFileName\" value=\"\" class=\"form-file-name\"/>";
            echo $oldFieldName;
            echo "</div>";
        }else if ($ctrl["readonly"]){
            echo "<div class=\"single-file-container\">";
            echo "</div>";
        }else{
            echo $myOut;
        }*/
        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }
    
    function getTextForm($name, $value = "", $width = 12, $placeholder = "", $label_text = "", $validator = [], $textPrefix = ""){
        $unique_id = htmlspecialchars($this->getUniqueIDfromName($name));
        $editable = $this->checkWritePermission($name);
        $out = "";
        
        if ($editable){
            $additonal_array = $this->constructValidatorStrings($validator);
            $type = "text";
            if (isset($validator["email"]))
                $type = "email";
            $additonal_str = implode(" ", $additonal_array);
            
            $value = htmlspecialchars($value);
            if (isset($textPrefix) && !empty($textPrefix)){
                $out .= "<div class='input-group'>";
                $out .= "<div class='input-group-addon'>" . $textPrefix . "</div>";
            }
            $out .= "<input type='$type' class='form-control' id='$unique_id' name='$name' value='$value' placeholder='{$placeholder}' $additonal_str >";
            if (isset($textPrefix) && !empty($textPrefix)){
                $out .= "</div>";
            }
        }else{
            $out .= "<div id='$unique_id'>" . htmlspecialchars($textPrefix) . " - " . $this->getReadOnlyValue($value) . "</div>";
        }
        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }
    
    public function getHyperLink($text, $type, $id){
        
        $out = "<a href='" . $GLOBALS["URIBASE"] . $type . "/" . $id . "'><i class='fa fa-fw fa-chain'></i>&nbsp;" . htmlspecialchars($text) . "</a>";
        return $out;
    }
    
    public function getMoneyForm($name, $value = 0, $width = 12, $placeholder = "0.00", $label_text = "", $validator = [], $sum_id = ""){
        $out = "";
        $unique_id = $this->getUniqueIDfromName($name);
        $editable = $this->checkWritePermission($name);
        
        if ($editable){
            $additonal_array = $this->constructValidatorStrings($validator);
            $additonal_str = implode(" ", $additonal_array);
            $value = htmlspecialchars(number_format($value, 2, ",", ""));
            $out .= "<div class='input-group'>";
            $out .= "<input id='$unique_id' name='$name' type='text' placeholder='$placeholder' class='form-control text-right' value='$value' data-addtosum='$sum_id' $additonal_str>";
            $out .= "<span class='input-group-addon'>€</span>";
            $out .= "</div>";
        }else{
            $out .= "<div class='money' id='$unique_id' data-addtosum='$sum_id'>" . htmlspecialchars(number_format($value, 2, ",", ".")) . "&nbsp;€</div>";
        }
        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }
    
    public function getDropdownForm($name, $selectable = [], $width = 12, $placeholder = "", $label_text = "", $validator = [], $searchable = true){
        $out = "";
        $editable = $this->checkWritePermission($name);
        $unique_id = $this->getUniqueIDfromName($name);
        
        $values = [];
        if (isset($selectable["values"])){
            if (is_array($selectable["values"])){
                $values = $selectable["values"];
            }else{
                $values = explode(",", $selectable["values"]);
            }
        }
        //var_dump($values);
        if ($editable){
            $additonal_array = $this->constructValidatorStrings($validator);
            $additonal_array[] = "data-live-search=" . ($searchable ? "'true'" : "'false'");
            $additonal_str = implode(" ", $additonal_array);
            $out .= "<select name='$name' id='$unique_id' class='selectpicker form-control' title='$placeholder' $additonal_str>";
            foreach ($selectable["groups"] as $group){
                if (isset($group["label"])){
                    $group_label = $group["label"];
                }else{
                    $group_label = "";
                }
                $out .= "<optgroup label='$group_label'>";
                foreach ($group["options"] as $option){
                    if ($option["label"] === null)
                        continue;
                    if (isset($option["value"]))
                        $val = $option["value"];
                    else
                        $val = $option["label"];
                    $sub = "";
                    if (isset($option["subtext"]))
                        $sub = $option["subtext"];
                    $out .= "<option data-subtext='$sub' value='$val' " . (in_array($val, $values) ? "selected" : "") . ">{$option['label']}</option>";
                }
                $out .= "</optgroup>";
            }
            $out .= "</select>";
        }else{
            $tmp_vals = [];
            foreach ($selectable["groups"] as $group){
                foreach ($group["options"] as $option){
                    if (isset($option["value"]) && in_array($option["value"], $values)){
                        $tmp_vals[$option["value"]] = $option["label"];
                    }
                }
            }
            $values = array_merge(array_diff($values, array_keys($tmp_vals)), array_values($tmp_vals));
            $out .= "<div data-name='$name' data-value='" . implode(",", array_keys($tmp_vals)) . "' id='$unique_id'>" . $this->getReadOnlyValue($values) . "</div>";
        }
        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }
    
    function getTextareaForm($name, $value = "", $width = 12, $placeholder = "", $label_text = "", $validator = [], $min_rows = 5){
        $out = "";
        $editable = $this->checkWritePermission($name);
        $unique_id = $this->getUniqueIDfromName($name);
        
        if ($editable){
            $additonal_array = $this->constructValidatorStrings($validator);
            $additonal_str = implode(" ", $additonal_array);
            $out .= "<textarea id='$unique_id' placeholder='$placeholder' name='$name' class='form-control' rows='$min_rows' $additonal_str>$value</textarea>";
        }else{
            
            $out .= "<div id='$unique_id'>{$this->getReadOnlyValue($value)}</div>";
        }
        
        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }
    
    function getDatePickerForm($names, $value = "", $width = 12, $placeholder = "", $label_text = "", $validator = [], $daterange = false, $startDate = ""){
        if (!is_array($value)){
            $value = [$value, $value];
        }
        if (!isset($placeholder) || !is_array($placeholder)){
            $placeholder = [$placeholder, $placeholder];
        }
        if (!is_array($names)){
            if ($daterange){
                $names = [$names . "[]", $names . "[]"];
            }else{
                $names = [$names];
            }
        }
        $out = "";
        $editable = $this->checkWritePermission($names);
        $unique_id0 = $this->getUniqueIDfromName($names[0]);
        
        if ($editable){
            $additonal_array = $this->constructValidatorStrings($validator);
            
            $additonal_str = implode(" ", $additonal_array);
            $out .= "<div id='$unique_id0' class='input-group " . ($daterange ? "input-daterange" : "date") . "' data-provide='datepicker' data-date-format='yyyy-mm-dd' data-date-calendar-weeks='true' data-date-language='de' data-date-start-date='$startDate'>";
            if ($daterange){
                $out .= "<div class='input-group-addon' style='background-color: transparent; border: none;'>von</div>";
                $out .= "<div class='input-group'>";
            }
            $out .= "    <input class='form-control' name='{$names[0]}' placeholder='{$placeholder[0]}' id='$unique_id0' $additonal_str value='{$value[0]}' type='text'>";
            $out .= "    <div class='input-group-addon'>";
            $out .= "        <span class='fa fa-fw fa-calendar'></span>";
            $out .= "    </div>";
            if ($daterange){
                $unique_id1 = $this->getUniqueIDfromName($names[1]);
                $out .= "</div>";
                $out .= "<div class='input-group-addon' style='background-color: transparent; border: none;'>bis</div>";
                $out .= "<div class='input-group'>";
                $out .= "    <input class='form-control' id='$unique_id1' name='{$names[1]}' value='{$value[1]}' placeholder='{$placeholder[1]}' $additonal_str type='text'>";
                $out .= "    <div class='input-group-addon'>";
                $out .= "        <span class='fa fa-fw fa-calendar'></span>";
                $out .= "    </div>";
                $out .= "</div>";
            }
            $out .= "</div>";
        }else{
            $out .= "<div id='$unique_id0'>";
            
            if ($daterange){
                $out .= "<strong>von&nbsp;</strong>";
                $out .= "<span>{$this->getReadOnlyValue($value[0])}</span>";
                $out .= "<strong>&nbsp;bis&nbsp;</strong>";
                $out .= "<span>{$this->getReadOnlyValue($value[1])}</span>";
            }else{
                $out .= "<strong>am </strong>";
                $out .= "<span>{$this->getReadOnlyValue($value[0])}</span>";
            }
            $out .= "</div>";
        }
        return $this->getOutputWrapped($out, $width, $editable, $names[0], $unique_id0, $label_text, $validator);
    }
    
    public function getHiddenActionInput($actionName){
        $out = "<input type='hidden' name='action' value='$actionName'>";
        return $out;
    }
    
}

