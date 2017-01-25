<?php
# vim: set syntax=php:

$formlayout = getFormLayout($antrag["type"],$antrag["revision"]);
if ($formlayout === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

renderForm($formlayout, ["_values" => $antrag, "render" => ["no-form"]] );

