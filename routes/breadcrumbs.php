<?php

// routes/breadcrumbs.php

// Note: Laravel will automatically resolve `Breadcrumbs::` without
// this import. This is nice for IDE syntax and refactoring.
use Diglactic\Breadcrumbs\Breadcrumbs;
// This import is also not required, and you could replace `BreadcrumbTrail $trail`
//  with `$trail`. This is nice for IDE type checking and completion.
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

/**
 *  legacy
 */

// Home
Breadcrumbs::for('legacy.dashboard', static function (BreadcrumbTrail $trail): void {
    $trail->push('Home', route('legacy.dashboard', 'mygremium'));
});

// Home > TODOS
Breadcrumbs::for('legacy.todo.belege', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.todo'), route('legacy.todo.belege'));
});

Breadcrumbs::for('legacy.todo.hv', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.todo'), route('legacy.todo.belege'));
});

Breadcrumbs::for('legacy.todo.kv', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.todo'), route('legacy.todo.belege'));
});

Breadcrumbs::for('legacy.todo.kv.bank', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.todo'), route('legacy.todo.belege'));
});

// Home > Booking
Breadcrumbs::for('legacy.booking', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.booking'), route('legacy.booking'));
});

Breadcrumbs::for('legacy.booking.instruct', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.booking.text'), route('legacy.booking'));
});

Breadcrumbs::for('legacy.booking.text', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.booking.text'), route('legacy.booking'));
});

Breadcrumbs::for('legacy.booking.history', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.booking.history'), route('legacy.booking'));
});

// Home > Konto
Breadcrumbs::for('legacy.konto', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.konto'), route('legacy.konto'));
});

// Home > Konto > New
Breadcrumbs::for('bank-account.new', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.konto');
    $trail->push(__('general.breadcrumb.konto.new'), route('bank-account.new'));
});

// Home > Konto > Import
Breadcrumbs::for('bank-account.import.csv', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.konto');
    $trail->push(__('general.breadcrumb.konto.import.csv'), route('bank-account.import.csv'));
});

// Home > Konto > Credentials
Breadcrumbs::for('legacy.konto.credentials', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.konto');
    $trail->push(__('general.breadcrumb.konto.credentials'), route('legacy.konto.credentials'));
});

// Home > Konto > Credentials
Breadcrumbs::for('legacy.konto.credentials.new', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.konto.credentials');
    $trail->push(__('general.breadcrumb.konto.credentials.new'), route('legacy.konto.credentials.new'));
});

// Home > Konto > Credentials > Login
Breadcrumbs::for('legacy.konto.credentials.login', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.konto.credentials');
    $trail->push(__('general.breadcrumb.konto.login'));
});

// Home > Konto > Credentials > TAN Mode
Breadcrumbs::for('legacy.konto.credentials.tan-mode', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.konto.credentials');
    $trail->push(__('general.breadcrumb.konto.tan-mode'));
});

// Home > Konto > Credentials > Sepa
Breadcrumbs::for('legacy.konto.credentials.sepa', static function (BreadcrumbTrail $trail, $credential_id): void {
    $trail->parent('legacy.konto.credentials');
    $trail->push(__('general.breadcrumb.konto.sepa'), route('legacy.konto.credentials.sepa', $credential_id));
});

// Home > Konto > Credentials > Sepa
Breadcrumbs::for('legacy.konto.credentials.import-konto', static function (BreadcrumbTrail $trail, $credential_id, $shortIban): void {
    $trail->parent('legacy.konto.credentials.sepa', $credential_id);
    $trail->push(__('general.breadcrumb.konto.import-konto'));
});

// Home > Sitzung
Breadcrumbs::for('legacy.sitzung', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.sitzung'), route('legacy.sitzung'));
});

// Home > HHP
Breadcrumbs::for('legacy.hhp', static function (BreadcrumbTrail $trail) {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.budget-plan'), route('legacy.hhp'));
});

// Home > HHP > Import
Breadcrumbs::for('legacy.hhp.import', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.hhp');
    $trail->push(__('general.breadcrumb.budget-plan-import'), route('legacy.hhp.import'));
});

// Home > HHP > $hhp_id
Breadcrumbs::for('legacy.hhp.view', static function (BreadcrumbTrail $trail, $hhp_id): void {
    $trail->parent('legacy.hhp');
    $trail->push($hhp_id, route('legacy.hhp.view', $hhp_id));
});

// Home > HHP > $hhp_id > Titel-Details
Breadcrumbs::for('legacy.hhp.titel.view', static function (BreadcrumbTrail $trail, int $hhp_id, int $title_id): void {
    $trail->parent('legacy.hhp.view', $hhp_id);
    $trail->push(__('general.breadcrumb.hhp-title-details'), route('legacy.hhp.titel.view', [$hhp_id, $title_id]));
});

// Home > Projekt > PID
Breadcrumbs::for('legacy.projekt', static function (BreadcrumbTrail $trail, $projekt_id): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.projekt'));
    $trail->push($projekt_id, route('legacy.projekt', $projekt_id));
});

// Home > Projekt > PID > Abrechnung > AID
Breadcrumbs::for('legacy.expense-long', static function (BreadcrumbTrail $trail, $projekt_id, $auslagen_id): void {
    $trail->parent('legacy.projekt', $projekt_id);
    $trail->push(__('general.breadcrumb.abrechnung'));
    $trail->push($auslagen_id, route('legacy.expense', $auslagen_id));
});

// Home > Projekt > PID > Abrechnung > AID > BelegePDF
Breadcrumbs::for('legacy.belege-pdf', static function (BreadcrumbTrail $trail, $projekt_id, $auslagen_id, $version): void {
    $trail->parent('legacy.expense-long', $projekt_id, $auslagen_id);
    $trail->push(__('general.breadcrumb.belege-pdf'));
});

// Home > Projekt > PID > Abrechnung > AID > Zahlungsanweisung
Breadcrumbs::for('legacy.zahlungsanweisung-pdf', static function (BreadcrumbTrail $trail, $projekt_id, $auslagen_id, $version): void {
    $trail->parent('legacy.expense-long', $projekt_id, $auslagen_id);
    $trail->push(__('general.breadcrumb.zahlungsanweisung-pdf'));
});

/**
 * not legacy
 */

// Home > Budget-Plans
Breadcrumbs::for('budget-plan.index', static function (BreadcrumbTrail $trail): void {
    $trail->parent('legacy.dashboard');
    $trail->push(__('general.breadcrumb.budget-plan'), route('budget-plan.index'));
});

// Home > Budget-Plans > ID
Breadcrumbs::for('budget-plan.view', static function (BreadcrumbTrail $trail, $plan_id): void {
    $trail->parent('budget-plan.index');
    $trail->push($plan_id, route('budget-plan.view', $plan_id));
});

// Home > Budget-Plans > ID
Breadcrumbs::for('budget-plan.edit', static function (BreadcrumbTrail $trail, $plan_id): void {
    $trail->parent('budget-plan.view', $plan_id);
    $trail->push(__('general.breadcrumb.budget-plan-edit'), route('budget-plan.edit', $plan_id));
});
