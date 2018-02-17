
<?php
# vim: set syntax=php:

$form = getForm($antrag["type"],$antrag["revision"]);
if ($form === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");
$render = ["no-form"];
if (isset($form["config"]["renderOptRead"]))
  $render = array_merge($render, $form["config"]["renderOptRead"]);

renderForm($form, ["_values" => $antrag, "render" => $render] );

