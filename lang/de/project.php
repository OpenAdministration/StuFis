<?php

return [
    'stateNames' => [
        'draft' => 'Entwurf',
        'wip' => 'Beantragt',
        'ok-by-hv' => 'Genehmigt durch HV (nicht verkündet)',
        'need-stura' => 'Warte auf Gremien-Beschluss',
        'ok-by-stura' => 'Genehmigt durch Gremien-Beschluss',
        'done-hv' => 'verkündet durch HV',
        'done-other' => 'Genehmigt',
        'revoked' => 'Abgelehnt / Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)',
        'terminated' => 'Abgeschlossen (keine weiteren Ausgaben)',
    ],
    'stateActions' => [
        'draft' => '',
        'wip' => 'beantragen',
        'ok-by-hv' => '',
        'need-stura' => '',
        'ok-by-stura' => '',
        'done-hv' => '',
        'done-other' => '',
        'revoked' => 'zurückziehen / ablehnen',
        'terminated' => 'beenden',
    ],
    'error' => [
        'posten_illegal_deleted' => 'Posten mit denen noch eine Abrechnung existiert dürfen nicht gelöscht werden!'
    ]
];
