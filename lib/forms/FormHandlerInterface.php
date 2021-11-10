<?php

namespace forms;

use framework\render\Renderer;

abstract class FormHandlerInterface extends Renderer
{
    abstract public static function initStaticVars();

    abstract public static function getStateStringFromName($statename);

    abstract public function updateSavedData($data);

    abstract public function setState($stateName);

    abstract public function getNextPossibleStates();

    abstract public function getID(): ?int;
}
