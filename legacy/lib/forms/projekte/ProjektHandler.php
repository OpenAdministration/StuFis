<?php

namespace forms\projekte;

use App\Exceptions\LegacyDieException;
use App\Models\Legacy\ExpenseReceiptPost;
use App\Models\Legacy\Project;
use App\Models\Legacy\ProjectPost;
use App\Models\User;
use App\States\Project\Draft;
use App\States\Project\ProjectState;
use forms\chat\ChatHandler;
use forms\FormTemplaterProject;
use forms\projekte\auslagen\AuslagenHandler2;
use forms\projekte\exceptions\IllegalStateException;
use forms\projekte\exceptions\InvalidDataException;
use forms\projekte\exceptions\WrongVersionException;
use framework\auth\AuthHandler;
use framework\DBConnector;
use framework\render\HTMLPageRenderer;
use framework\render\Renderer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PDOException;

class ProjektHandler extends Renderer
{
    private static $emptyData;

    private static $visibleFields;

    private static $writePermissionAll;

    private static $writePermissionFields;

    private $templater;

    private $stateHandler;

    /**
     * @var PermissionHandler
     */
    private $id;

    private $action;

    private $data;

    public function __construct($pathInfo)
    {
        // print_r($pathInfo);
        self::initStaticVars();
        if (! isset($pathInfo['action'])) {
            throw new LegacyDieException(400, 'Aktion nicht gesetzt');
        }
        $this->action = $pathInfo['action'];
        if ($this->action === 'create' || ! isset($pathInfo['pid'])) {
            $this->data = self::$emptyData;
            $this->templater = new FormTemplaterProject(new Project);
        } else {
            $this->id = $pathInfo['pid'];
            $project = Project::findOrFail($this->id);
            $this->data = $project->getAttributes();

            foreach ($project->posts as $idx => $post) {
                $this->data['posten-id'][$idx + 1] = $post->id;
                $this->data['posten-name'][$idx + 1] = $post->name;
                $this->data['posten-bemerkung'][$idx + 1] = $post->bemerkung;
                $this->data['posten-einnahmen'][$idx + 1] = $post->einnahmen->getAmount() / 100;
                $this->data['posten-ausgaben'][$idx + 1] = $post->ausgaben->getAmount() / 100;
                $this->data['posten-titel'][$idx + 1] = $post->titel_id;
            }
            $this->templater = new FormTemplaterProject($project);
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
        self::$visibleFields = [
            'recht' => [
                'wip',
                'ok-by-hv',
                'need-stura',
                'ok-by-stura',
                'done-hv',
                'done-other',
                'terminated',
            ],
            'posten-titel' => [
                'wip',
                'ok-by-hv',
                'need-stura',
                'ok-by-stura',
                'done-hv',
                'done-other',
                'terminated',
            ],
            'createdat' => [
                'wip',
                'ok-by-hv',
                'need-stura',
                'ok-by-stura',
                'done-hv',
                'done-other',
                'terminated',
            ],
        ];
        self::$writePermissionAll = [
            'draft' => ['groups' => ['login']],
            'wip' => ['groups' => ['ref-finanzen-hv']],
            'ok-by-hv' => ['groups' => ['ref-finanzen-hv']],
            'need-stura' => ['groups' => ['ref-finanzen-hv']],
            'ok-by-stura' => ['groups' => ['ref-finanzen-hv']],
            'done-hv' => ['groups' => ['ref-finanzen-hv']],
            'done-other' => ['groups' => ['ref-finanzen-hv']],
            'terminated' => [],
            'revoked' => [],
        ];
        self::$writePermissionFields = [
            'ok-by-hv' => [
                'recht_additional' => ['groups' => ['ref-finanzen-hv']],
            ],
        ];

        return true;
    }

    /**
     * @throws InvalidDataException
     * @throws PDOException
     */
    public static function createNewProjekt($data): ProjektHandler
    {
        $maxRows = max(
            count($data['posten-name']),
            count($data['posten-bemerkung']),
            count($data['posten-einnahmen']),
            count($data['posten-ausgaben'])
        );
        $minRows = min(
            count($data['posten-name']),
            count($data['posten-bemerkung']),
            count($data['posten-einnahmen']),
            count($data['posten-ausgaben'])
        );

        if ($maxRows !== $minRows) {
            throw new InvalidDataException('Projekt-Zeilen ungleichmäßig übertragen');
        }

        $user_id = DBConnector::getInstance()->getUser()['id'];
        $projekt_id = DBConnector::getInstance()->dbInsert(
            'projekte',
            [
                'creator_id' => $user_id,
                'createdat' => date('Y-m-d H:i:s'),
                'lastupdated' => date('Y-m-d H:i:s'),
                'version' => 1,
                'state' => 'draft',
                'stateCreator_id' => $user_id,
                'name' => $data['name'],
                'responsible' => $data['responsible'],
                'org' => $data['org'],
                'org_mail' => $data['org_mail'] ?? '',
                'protokoll' => $data['protokoll'] ?? '',
                'beschreibung' => $data['beschreibung'],
                'date_start' => $data['date_start'],
                'date_end' => $data['date_end'],
            ]
        );

        for ($i = 0; $i < $minRows - 1; $i++) {
            if ((float) $data['posten-ausgaben'][$i] > 0 && (float) $data['posten-einnahmen'][$i] > 0) {
                throw new InvalidDataException(
                    'Projektposten dürfen nicht gleichzeitig Einnahmen und Ausgaben enthalten.'
                );
            }
            DBConnector::getInstance()->dbInsert(
                'projektposten',
                [
                    'id' => $i + 1,
                    'projekt_id' => $projekt_id,
                    'einnahmen' => DBConnector::getInstance()->convertUserValueToDBValue(
                        $data['posten-einnahmen'][$i],
                        'money'
                    ),
                    'ausgaben' => DBConnector::getInstance()->convertUserValueToDBValue(
                        $data['posten-ausgaben'][$i],
                        'money'
                    ),
                    'name' => $data['posten-name'][$i],
                    'bemerkung' => $data['posten-bemerkung'][$i],
                ]
            );
        }

        return new ProjektHandler(['pid' => $projekt_id, 'action' => 'none']);
    }

    public static function getStateStringFromName(string $statename)
    {
        $state = ProjectState::make($statename, new Project);

        return $state->label();
    }

    /**
     * @throws PDOException
     * @throws WrongVersionException
     */
    public function updateSavedData($data): bool
    {
        DB::beginTransaction();
        $project = Project::findOrFail($this->id);
        $data = array_intersect_key($data, self::$emptyData);
        $version = (int) $data['version'];

        // check if version is the same
        if (((int) $version) !== ((int) $this->data['version'])) {
            throw new WrongVersionException('Projekt wurde zwischenzeitlich schon von jemand anderem bearbeitet!');
        }
        // check if row count is everywhere the same
        $maxRows = $minRows = 0;
        if (isset($data['posten-name'], $data['posten-bemerkung'], $data['posten-einnahmen'], $data['posten-ausgaben'])) {
            $maxRows = max(
                count($data['posten-name']),
                count($data['posten-bemerkung']),
                count($data['posten-einnahmen']),
                count($data['posten-ausgaben']),
                count($data['posten-id'])
            );
            $minRows = min(
                count($data['posten-name']),
                count($data['posten-bemerkung']),
                count($data['posten-einnahmen']),
                count($data['posten-ausgaben']),
                count($data['posten-id'])
            );
        }
        // wenn posten-titel nicht mit übertragen setze dummy an seine stelle
        if (! isset($data['posten-titel'])) {
            $data['posten-titel'] = array_fill(0, $maxRows, null);
        }

        // wenn anzahl der rows nicht identisch -> error
        if ($maxRows !== $minRows || count($data['posten-titel']) !== $minRows) {
            throw new InvalidDataException('Projekt-Zeilen ungleichmäßig übertragen');
        }
        // remove some Autogenerated values
        $generatedFields = [
            'id' => $this->id,
            'lastupdated' => date('Y-m-d H:i:s'),
            'version' => ($this->data['version'] + 1),
        ];
        // extract the posten fields, which go to a different table
        $extractFields = ['posten-name', 'posten-bemerkung', 'posten-einnahmen', 'posten-ausgaben', 'posten-titel', 'posten-id'];
        $extractFields = array_intersect_key($data, array_flip($extractFields));

        // remove the generated and extracted fields from the data array
        $data = array_diff_key($data, $generatedFields, $extractFields);

        $recht_unset = false;
        if (isset($data['recht_additional'])) {
            if (! isset($data['recht']) && isset($this->data['recht'])) {
                $data['recht'] = $this->data['recht'];
                $recht_unset = true;
            }
            if (! isset($data['recht'])) {
                $data['recht_additional'] = '';
            } elseif (isset($data['recht_additional'][$data['recht']])) {
                $data['recht_additional'] = $data['recht_additional'][$data['recht']];
            } else {
                $data['recht_additional'] = '';
            }
        }

        if ($recht_unset) {
            unset($data['recht']);
        }

        // check if fields editable
        $fields = $generatedFields;
        foreach ($data as $name => $content) {
            if (Auth::user()->can('update-field', [$project, $name])) {
                if (! empty($content)) {
                    $fields[$name] = $content;
                } else {
                    $fields[$name] = null;
                }
            } else {
                throw new LegacyDieException(403, "Du hast keine Berechtigung '$name' zu schreiben.");
            }
        }
        $project->update($fields);

        // update old posten, create new, delete old

        // update old posten (last minrow is empty all the time
        $nextFreeId = max($extractFields['posten-id']);

        // protocol which ids got used, so we can delete everything else afterwards
        $used_ids = [];

        for ($i = 0; $i < $minRows - 1; $i++) {
            $id = ((int) $extractFields['posten-id'][$i]);
            if ($id === 0) {
                $id = ++$nextFreeId;
            }
            $used_ids[] = $id;
            ProjectPost::updateOrInsert(['id' => $id, 'projekt_id' => $this->id], [
                'name' => $extractFields['posten-name'][$i],
                'bemerkung' => $extractFields['posten-bemerkung'][$i],
                // FIXME: use new money type
                'einnahmen' => DBConnector::getInstance()->convertUserValueToDBValue($extractFields['posten-einnahmen'][$i], 'money'),
                'ausgaben' => DBConnector::getInstance()->convertUserValueToDBValue($extractFields['posten-ausgaben'][$i], 'money'),
                'titel_id' => $extractFields['posten-titel'][$i],
            ]);
        }
        $project_id = $this->id;
        $used_posten_deleted = ExpenseReceiptPost::whereNotIn('projekt_posten_id', $used_ids)
            ->whereHas('expensesReceipt.expense', function ($query) use ($project_id) {
                $query->where('projekt_id', $project_id);
            })->exists();

        if ($used_posten_deleted) {
            throw new InvalidDataException(__('project.error.posten_illegal_deleted'));
        }

        ProjectPost::whereNotIn('id', $used_ids)->delete();

        DB::commit();

        return true;
    }

    /**
     * @throws IllegalStateException
     */
    public function setState($stateName): bool
    {
        $project = Project::findOrFail($this->id);
        $newState = ProjectState::make($stateName, $project);

        // Check if transtion is possible and user is authorized to make this transition
        Gate::authorize('transition-to', [$project, $newState]);

        // Start database transaction
        return DB::transaction(function () use ($project, $stateName, $newState) {

            // Create chat message for state transition
            $chat = new ChatHandler('projekt', $this->id);
            $chat->_createComment(
                'projekt',
                $this->id,
                now()->format('Y-m-d H:i:s'),
                'system',
                '',
                $project->state->label().' -> '.$newState->label(),
                1
            );

            // Update project state
            $project->update([
                'state' => $stateName,
                'stateCreator_id' => Auth::id(),
                'lastupdated' => now(),
                'version' => $project->version + 1,
            ]);

            return true;
        });
    }

    public function getNextPossibleStates(): array
    {
        return $this->stateHandler->getNextStates(true);
    }

    public function render(): void
    {
        if ($this->action === 'create' || ! isset($this->id)) {
            $this->renderProjekt('neues Projekt anlegen', true);

            return;
        }
        switch ($this->action) {
            case 'edit':
                $this->renderBackButton();
                $this->renderProjekt('Projekt bearbeiten', true);
                break;
            case 'view':
                $this->renderInteractionPanel();
                // echo $this->templater->getStateChooser($this->stateHandler);
                $this->renderProjekt('Projekt '.$this->id, false);
                $this->render_chat_box();
                $this->renderProjektSizeGrafic();
                $this->renderAuslagenList();
                break;
            default:
                throw new LegacyDieException(404, "Aktion: $this->action bei Projekt $this->id nicht bekannt.");
                break;
        }
    }

    private function renderProjekt($title, bool $edit): void
    {
        if ($edit) {
            $this->templater->wantToEdit();
        }
        $auth = AuthHandler::getInstance();
        $model = Project::find($this->id) ?? new Project;
        $validateMe = false;
        $editable = $edit && Auth::user()->can('update', $model);
        // build dropdowns
        $selectable_gremien = FormTemplaterProject::generateGremienSelectable();
        $selectable_gremien['values'] = $this->data['org'];

        $mailingLists = $auth->hasGroup('ref-finanzen') ? MAILINGLISTS : AuthHandler::getInstance()->getUserMailinglists();
        $selectable_mail = FormTemplaterProject::generateSelectable($mailingLists);
        $selectable_mail['values'] = $this->data['org_mail'];
        $sel_recht = FormTemplaterProject::generateSelectable(array_combine(
            array_keys(ORG_DATA['rechtsgrundlagen']),
            array_map(static function ($val) {
                return $val['label'];
            }, ORG_DATA['rechtsgrundlagen'])
        ));
        $sel_recht['values'] = $this->data['recht'];
        if (isset($this->data['createdat']) && ! empty($this->data['createdat'])) {
            $createDate = $this->data['createdat'];
        } else {
            $createDate = date_create()->format('Y-m-d');
        }
        $hhpId = DBConnector::getInstance()->dbFetchAll(
            'haushaltsplan',
            [DBConnector::FETCH_ASSOC],
            ['id'],
            [
                ['von' => ['<=', $createDate], 'bis' => ['>=', $createDate], 'state' => 'final'],
                ['von' => ['<=', $createDate], 'bis' => ['is', null], 'state' => 'final'],
            ]
        );
        if (empty($hhpId)) {
            throw new LegacyDieException(400, 'HHP-id kann nicht ermittelt werden. Bitte benachrichtigen sie den Administrator');
        }
        $hhpId = $hhpId[0]['id'];
        $selectable_titel = FormTemplaterProject::generateTitelSelectable($hhpId);
        ?>
        <div class='col-xs-12 col-md-10'>
            <?php
            if ($editable) { ?>
            <form role="form" action="<?= URIBASE.'rest/forms/projekt' ?>" method="POST"
                  enctype="multipart/form-data" class="ajax">
                <?php echo $this->templater->getHiddenActionInput(isset($this->id) ? 'update' : 'create'); ?>
                <input type="hidden" name="nonce" value="<?= csrf_token() ?>">
                <input type="hidden" name="version" value="<?php echo $this->data['version']; ?>">
                <?php if (isset($this->id)) { ?>
                    <input type="hidden" name="id" value="<?php echo $this->id; ?>">
                <?php } ?>
                <?php } // endif editable?>
                <?php if (! $model->state->equals(Draft::class)) { ?>
                    <h2>Genehmigung</h2>
                    <div class="well">
                        <div class="hide-wrapper">
                            <div class="hide-picker">
                                <?= $this->templater->getDropdownForm(
                                    'recht',
                                    $sel_recht,
                                    6,
                                    'Wähle Rechtsgrundlage...',
                                    'Rechtsgrundlage',
                                    ['required'],
                                    false
                                ); ?>
                            </div>
                            <div class="hide-items">
                                <?php foreach (ORG_DATA['rechtsgrundlagen'] as $shortName => $def) { ?>
                                    <div id="<?php echo $shortName; ?>" class="form-group" style="display: none;">
                                        <?php if (isset($def['placeholder'], $def['label-additional'])) {
                                            echo $this->templater->getTextForm(
                                                "recht_additional[$shortName]",
                                                $this->data['recht_additional'],
                                                4,
                                                $def['placeholder'] ?? '',
                                                $def['label-additional'] ?? 'Zusatzinformationen',
                                                []
                                            );
                                        } ?>
                                        <span class="col-xs-12"><?php echo $def['hint-text'] ?? ''; ?></span>
                                    </div>
                                    <?php
                                }
                    ?>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                    </div>
                <?php } ?>
                <h2><?= $title ?></h2>
                <div class="well">
                    <?= $this->templater->getTextForm(
                        'name',
                        $this->data['name'],
                        6,
                        '',
                        'Projektname',
                        ['required']
                    ); ?>
                    <?= $this->templater->getMailForm(
                        'responsible',
                        $this->data['responsible'],
                        6,
                        'vorname.nachname',
                        'Projektverantwortlich (Mail)',
                        ['required', 'email'],
                        '@'.ORG_DATA['mail-domain']
                    ); ?>
                    <div class="clearfix"></div>
                    <?= $this->templater->getDropdownForm(
                        'org',
                        $selectable_gremien,
                        6,
                        'Wähle Gremium ...',
                        'Organisation',
                        ['required'],
                        true
                    ); ?>
                    <?php if (count(ORG_DATA['mailinglists']) > 0) {
                        echo $this->templater->getDropdownForm(
                            'org_mail',
                            $selectable_mail,
                            6,
                            'Wähle Mailingliste ...',
                            'Organisations-Mail',
                            ['required'],
                            true
                        );
                    } ?>
                    <?php
                    if (! in_array('hide-protokoll', ORG_DATA['projekt-form'], true)) {
                        echo $this->templater->getWikiLinkForm(
                            'protokoll',
                            $this->data['protokoll'],
                            12,
                            '...',
                            ORG_DATA['projekt-form']['protokoll-label'] ?? 'Ergänzender-Link',
                            [],
                            ORG_DATA['projekt-form']['protokoll-prefix'] ?? ''
                        );
                    } ?>
                    <?= $this->templater->getDatePickerForm(
                        ['date_start', 'date_end'],
                        [$this->data['date_start'], $this->data['date_end']],
                        12,
                        ['Projekt-Start', 'Projekt-Ende'],
                        'Projektzeitraum',
                        ['required'],
                        true,
                        'today'
                    ); ?>
                    <?= $this->templater->getDatePickerForm(
                        'createdat',
                        $this->data['createdat'],
                        12,
                        '',
                        'Projekt erstellt am'
                    ); ?>

                    <div class='clearfix'></div>
                </div>
                <?php $tablePartialEditable = $editable && Auth::user()->can('update', $model) /*$this->permissionHandler->isEditable(
                    ['posten-name', 'posten-bemerkung', 'posten-einnahmen', 'posten-ausgaben'],
                    'and'
                );*/ ?>
                <table class="table table-striped summing-table <?= $tablePartialEditable ? 'dynamic-table' : 'dynamic-table-readonly'; ?>">
                    <thead>
                    <tr>
                        <th></th><!-- Id  -->
                        <th></th><!-- Nr.       -->
                        <th></th><!-- Trashbin  -->
                        <th class="">Ein/Ausgabengruppe</th>
                        <th class="">Bemerkung</th>
                        <th class=""><?= $model->state->equals(Draft::class) ? '' : 'Titel'; ?></th>
                        <th class="col-xs-2">Einnahmen</th>
                        <th class="col-xs-2">Ausgaben</th>
                    </tr>
                    </thead>
                    <tbody><?php
                    $this->data['posten-name'][] = '';
        foreach ($this->data['posten-name'] as $row_nr => $null) {
            $new_row = ($row_nr) === count($this->data['posten-name']);
            if ($new_row && ! $tablePartialEditable) {
                continue;
            }
            $sel_titel = $selectable_titel;
            if (isset($this->data['posten-titel'][$row_nr])) {
                $sel_titel['values'] = $this->data['posten-titel'][$row_nr];
            } ?>
                        <tr class="<?= $new_row ? 'new-table-row' : 'dynamic-table-row'; ?>">
                            <td><input type="hidden" name="posten-id[]" value="<?= $this->data['posten-id'][$row_nr] ?? ''; ?>"></td>
                            <td class="row-number">
                                <?= $row_nr; ?>.
                            </td>
                            <?php if ($tablePartialEditable) { ?>
                                <td class='delete-row'><a href='' class='delete-row'><i
                                            class='fa fa-fw fa-trash'></i></a></td>
                            <?php } else {
                                echo '<td></td>';
                            } ?>
                            <td><?= $this->templater->getTextForm(
                                'posten-name[]',
                                ! $new_row ? $this->data['posten-name'][$row_nr] : '',
                                null,
                                'Name des Postens',
                                '',
                                ['required']
                            ); ?></td>
                            <td><?= $this->templater->getTextForm(
                                'posten-bemerkung[]',
                                ! $new_row ? $this->data['posten-bemerkung'][$row_nr] : '',
                                null,
                                'optional',
                                '',
                                []
                            ); ?></td>
                            <td><?= $this->templater->getDropdownForm(
                                'posten-titel[]',
                                $sel_titel,
                                null,
                                'HH-Titel',
                                '',
                                [],
                                true
                            ); ?></td>
                            <td><?= $this->templater->getMoneyForm(
                                'posten-einnahmen[]',
                                ! $new_row ? $this->data['posten-einnahmen'][$row_nr] : 0,
                                null,
                                '',
                                '',
                                ['required'],
                                'einnahmen'
                            ); ?></td>
                            <td><?= $this->templater->getMoneyForm(
                                'posten-ausgaben[]',
                                ! $new_row ? $this->data['posten-ausgaben'][$row_nr] : 0,
                                null,
                                '',
                                '',
                                ['required'],
                                'ausgaben'
                            ); ?></td>
                        </tr>
                        <?php
        } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th></th><!-- hidden id -->
                        <th></th><!-- Nr.       -->
                        <th></th><!-- Trashbin  -->
                        <th></th><!-- Name      -->
                        <th></th><!-- Bemerkung -->
                        <th></th><!-- Titel -->
                        <th class="dynamic-table-cell cell-has-printSum">
                            <div class="form-group no-form-grp">
                                <div class="input-group input-group-static">
                                    <span class="input-group-addon">Σ</span>
                                    <div class="form-control-static nowrap text-right"
                                         data-printsum="einnahmen">0,00
                                    </div>
                                    <span class="input-group-addon">€</span>
                                </div>
                            </div>
                        </th><!-- einnahmen -->
                        <th class="dynamic-table-cell cell-has-printSum">
                            <div class="form-group no-form-grp">
                                <div class="input-group input-group-static">
                                    <span class="input-group-addon">Σ</span>
                                    <div class="form-control-static nowrap text-right"
                                         data-printsum="ausgaben">0,00
                                    </div>
                                    <span class="input-group-addon">€</span>
                                </div>
                            </div>
                        </th><!-- ausgaben -->
                    </tr>
                    </tfoot>
                </table>
                <?= $this->templater->getTextareaForm(
                    'beschreibung',
                    $this->data['beschreibung'],
                    12,
                    "In unserem Projekt geht es um ... \nHat einen Nutzen für die Studierendenschaft weil ... \nFindet dort und dort statt...\nusw.",
                    'Projektbeschreibung',
                    AuthHandler::getInstance()->hasGroup('ref-finanzen-hv') ?
                        [] : ['required', 'min-length' => ORG_DATA['projekt-form']['min-description-length'] ?? 100],
                    5
                ); ?>

                <?php if ($editable) { ?>
                <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
                <div class="pull-right">
                    <button type="submit"
                            class='btn btn-success submit-form <?php echo ! $validateMe ? 'no-validate' : 'validate'; ?>'
                            data-name="state" data-value="<?php echo htmlspecialchars($model->state); ?>"
                            id="state-<?php echo htmlspecialchars($model->state); ?>">Speichern
                        als <?php echo htmlspecialchars($model->state->label()); ?></button>
                </div>
            </form>
        <?php } ?>
        </div><!-- main-container -->
        <?php
    }

    private function renderBackButton(): void
    {
        ?>
        <div class="">
            <a href="./">
                <button class="btn btn-primary"><i class="fa fa-fw fa-arrow-left"></i>&nbsp;Zurück</button>
            </a>
        </div>
        <?php
    }

    private function renderInteractionPanel(): void
    {
        $url = str_replace('//', '/', URIBASE.'projekt/'.$this->id.'/');

        $project = Project::findOrFail($this->id);
        $nextStates = $project->state->transitionableStateInstances();
        $nextValidStates = [];
        $disabledStates = [];
        foreach ($nextStates as $nextState) {
            if (Auth::user()->can('transition-to', [$project, $nextState])) {
                $nextValidStates[] = $nextState;
            } else {
                $disabledStates[] = $nextState;
            }
        }
        ?>
        <div>
            <ul class="nav nav-pills nav-stacked navbar-right navbar-fixed-right">
                <li class="label-info">
                    <?php echo htmlspecialchars($project->state->label()); ?>
                </li>
                <?php if (count($nextValidStates) > 0) { ?>
                    <li><a href="#" data-toggle="modal" data-target="#editStateModal">
                            Status ändern <i class="fa fa-fw fa-refresh"></i></a>
                    </li>
                <?php } ?>
                <?php if (Auth::user()->can('create-expense', $project)) { ?>
                    <li><a href="<?php echo $url; ?>auslagen" title="Neue Abrechnung/Rechnung">
                            neue Abrechnung&nbsp;<i class="fa fa-fw fa-plus" aria-hidden="true"></i>
                        </a></li>
                <?php } ?>
                <?php if (Auth::user()->can('update', $project)) { ?>
                    <li><a href="<?php echo $url; ?>edit" title="Bearbeiten">
                            Bearbeiten&nbsp;<i class="fa fa-fw fa-pencil" aria-hidden="true"></i>
                        </a></li>
                <?php } ?>
                <li><a href="#" data-toggle="modal" data-target="#projekt-delete-dlg">Projekt löschen&nbsp;<i
                            class="fa fa-fw fa-trash"></i></a></li>
                <li><a href="<?= route('project.show', $this->id) ?>">Zur neuen Ansicht&nbsp;<i class="fa fa-fw fa-arrow-circle-o-right"></i></a></li>

                <!-- FIXME LIVE COMMENT ONLY
                <li><a href="<?php echo $url; ?>history" title="Verlauf">Historie <i class="fa fa-fw fa-history"
                                                                             aria-hidden="true"></i></a></li>
                <li><a href="<?php echo $url; ?>delete">Antrag löschen <i class="fa fa-trash" aria-hidden="true"></i></a></li>
                <li><a href="https://wiki.stura.tu-ilmenau.de/leitfaden/finanzenantraege">Hilfe
                        <i class="fa fa-question" aria-hidden="true"></i></a></li> -->
            </ul>
        </div>
        <?php if (count($nextValidStates) > 0) { ?>
        <!-- Modal Zustandsübergang zu anderem State -->
        <form id="stateantrag" role="form" action="<?php echo URIBASE.'rest/forms/projekt'; ?>"
              method="POST" enctype="multipart/form-data" class="ajax" data-toggle="validator">
            <div class="modal fade" id="editStateModal" tabindex="-1" role="dialog"
                 aria-labelledby="editStateModalLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="editStateModalLabel">Status wechseln</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="changeState">
                            <input type="hidden" name="nonce" value="<?= csrf_token() ?>">
                            <input type="hidden" name="version" value="<?php echo $this->data['version']; ?>">
                            <input type="hidden" name="id" value="<?php echo $this->getID(); ?>">
                            <div class="form-group">
                                <label for="newantragstate">Neuer Bearbeitungsstatus</label>
                                <select class="selectpicker form-control" name="newState" size="1"
                                        title="Neuer Bearbeitungsstatus" required="required" id="newantragstate">
                                    <optgroup label="Statuswechsel möglich">
                                        <?php
                                        foreach ($nextValidStates as $state) {
                                            echo '<option value="'.htmlspecialchars(
                                                $state
                                            ).'">'.htmlspecialchars($state->label()).'</option>'.PHP_EOL;
                                        }
            ?>
                                    </optgroup>
                                    <optgroup label="Daten unvollständig">
                                        <?php
            foreach ($disabledStates as $state) {
                echo '<option disabled>'.$state->label().'</option>'.PHP_EOL;
            }
            ?>
                                    </optgroup>
                                </select>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" name="absenden" class="btn btn-primary pull-right">Speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php }
        $hasPermission = $this->isOwner() || AuthHandler::getInstance()->hasGroup('ref-finanzen-hv');
        $hasNoAbrechnungen = $this->hasAuslagen() === false;
        $permissionIcon = $hasPermission ? 'fa-check' : 'fa-ban';
        $abrechnungIcon = $hasNoAbrechnungen ? 'fa-check' : 'fa-ban';
        ?>
        <form action="<?= route('legacy.projekt.delete', ['projekt_id' => $this->id]) ?>" method="POST">
            <input type="hidden" name="nonce" value="<?= csrf_token() ?>">
            <?php
            HTMLPageRenderer::injectModal(
                'projekt-delete',
                "<div class='js-head'>Wirklich Löschen?</div>",
                "Dieses Projekt kann endgültig gelöscht werden wenn,
            <ul>
                <li>du Projektersteller*in oder Haushaltsverantwortliche*r bist <i class='fa $permissionIcon'></i></li>
                <li>im Projekt keine Abrechnungen (mehr) vorhanden sind <i class='fa $abrechnungIcon'></i></li>
            </ul>
            Wenn das Projekt gelöscht wird, werden alle Daten dazu entfernt und können nicht wieder hergestellt werden.
            ",
                'Abbrechen',
                'Unwiderruflich Löschen',
                'danger',
                canConfirm: function () use ($hasPermission, $hasNoAbrechnungen) {
                    return $hasPermission && $hasNoAbrechnungen;
                },
                actionButtonType: 'submit'
            );
        ?>
        </form>
        <?php
    }

    public function getID(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return User::find((int) $this->data['creator_id']);
    }

    public function isOwner(): bool
    {
        return isset($this->data['creator_id']) && \Auth::user()->id === $this->data['creator_id'];
    }

    private function render_chat_box(): void
    { ?>
        <div class='clearfix'></div>
        <div class="col-xs-12 col-md-10" id="projektchat">
            <?php
            $auth = AuthHandler::getInstance();
        $btns = [];
        $pdate = date_create(substr($this->data['createdat'], 0, 4).'-01-01 00:00:00');
        $pdate->modify('+1 year');
        $now = date_create();
        // allow chat only 90 days into next year
        if ($now->getTimestamp() - $pdate->getTimestamp() <= 86400 * 90) {
            $btns[] = ['label' => 'Senden', 'color' => 'success', 'type' => '0'];
            /*
            if ($auth->hasGroup('ref-finanzen') || $auth->getUsername() === $this->data['username']) {
                $btns[] = [
                    'label' => 'Private Nachricht',
                    'color' => 'warning',
                    'type' => '-1',
                    'hover-title' => 'Private Nachricht zwischen Ref-Finanzen und dem Projekt-Ersteller'
                ];
            }
            */
            if ($auth->hasGroup('ref-finanzen')) {
                $btns[] = ['label' => 'Finanz Nachricht', 'color' => 'primary', 'type' => '3'];
            }
            if ($auth->hasGroup('admin')) {
                $btns[] = ['label' => 'Admin Nachricht', 'color' => 'danger', 'type' => '2'];
            }
        }
        ChatHandler::renderChatPanel(
            'projekt',
            $this->id,
            $auth->getUserFullName().' ('.$auth->getUsername().')',
            $btns
        ); ?>
        </div>
        <?php
    }

    private function renderProjektSizeGrafic(): void
    {
        /* echo '<div class="clearfix"></div>' . PHP_EOL;
        $ah = new AuslagenHandler2(['pid' => $this->id, 'action' => 'view']);
        $ah->render_auslagen_beleg_diagrams('Nice Diagrams'); */

    }

    private function renderAuslagenList(): void
    { ?>
        <div class="clearfix"></div>
        <div id='projekt-well' class="well col-xs-12 col-md-10">
            <?php
            $ah = new AuslagenHandler2(['pid' => $this->id, 'action' => 'view']);
        $ah->render_project_auslagen(true); ?>
        </div>
        <?php
    }

    private function hasAuslagen(): bool
    {
        return \DB::table('auslagen')->where('projekt_id', $this->id)->count() > 0;
    }
}
