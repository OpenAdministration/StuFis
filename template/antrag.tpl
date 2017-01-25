<?php
# vim: set syntax=php:

$formconfig = getFormConfig($antrag["type"],$antrag["revision"]);
if ($formconfig === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

renderForm($formconfig, ["_values" => $antrag, "render" => ["no-form"]] );

