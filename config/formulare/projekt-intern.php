<?php

$config = [
  "title" => "Finanzantrag für ein Projekt der Studierendenschaft (internes Projekt)",
  "state" => [ "draft" => "Entwurf", "new" => "eingereicht", "done" => "erledigt", "rejected" => "abgelehnt", "obsolete" => "veraltet / wird nicht bearbeitet" ],
  "createState" => "draft",
];

registerFormClass( "projekt-intern", $config );

