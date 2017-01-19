<?php
# vim: set syntax=php:

global $formid;
$formconfig = getFormConfig($antrag["type"],$antrag["revision"]);
if ($formconfig === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

renderForm($formconfig, ["_values" => $antrag, "render" => ["no-form"]] );

