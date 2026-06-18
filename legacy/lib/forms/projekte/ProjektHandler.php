<?php

namespace forms\projekte;

use App\Exceptions\LegacyDieException;
use App\Models\Legacy\Project;
use App\States\Project\ProjectState;

class ProjektHandler
{
    private static $emptyData;

    private $action;

    private $data;

    public function __construct($pathInfo)
    {
        self::initStaticVars();
        if (! isset($pathInfo['action'])) {
            throw new LegacyDieException(400, 'Aktion nicht gesetzt');
        }
        $this->action = $pathInfo['action'];
        if ($this->action === 'create' || ! isset($pathInfo['pid'])) {
            $this->data = self::$emptyData;
        } else {
            $project = Project::findOrFail($pathInfo['pid']);
            $this->data = $project->getAttributes();
        }
    }

    public static function initStaticVars(): bool
    {
        self::$emptyData = [
            'id' => '',
            'creator_id' => '',
            'createdat' => '',
            'lastupdated' => '',
            'version' => '1',
            'state' => 'draft',
            'stateCreator_id' => '',
            'name' => '',
            'responsible' => '',
            'org' => '',
            'org_mail' => '',
            'protokoll' => '',
            'beschreibung' => '',
            'recht' => '',
            'recht_additional' => '',
            'posten-id' => [1 => ''],
            'posten-name' => [1 => ''],
            'posten-bemerkung' => [1 => ''],
            'posten-titel' => [1 => ''],
            'posten-einnahmen' => [1 => 0],
            'posten-ausgaben' => [1 => 0],
            'date_start' => '',
            'date_end' => '',
        ];

        return true;
    }

    public static function getStateStringFromName(string $statename)
    {
        $state = ProjectState::make($statename, new Project);

        return $state->label();
    }

    public function isOwner(): bool
    {
        return isset($this->data['creator_id']) && \Auth::user()->id === $this->data['creator_id'];
    }
}
