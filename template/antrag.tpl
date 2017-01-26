<?php
# vim: set syntax=php:

$form = getForm($antrag["type"],$antrag["revision"]);
if ($form === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

renderForm($form, ["_values" => $antrag, "render" => ["no-form"]] );

