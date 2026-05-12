<?php

namespace Tests\Pest\Project;

use App\Models\Legacy\Project;
use App\States\Project\Applied;
use App\States\Project\ApprovedByFinance;
use App\States\Project\ApprovedByOrg;
use App\States\Project\ApprovedByOther;
use App\States\Project\Draft;
use App\States\Project\NeedFinanceApproval;
use App\States\Project\NeedOrgApproval;
use App\States\Project\Revoked;
use App\States\Project\Terminated;

beforeEach(function () {
    $this->actingAs(user());
});

// =============================================================================
// update() — full state × role matrix
// =============================================================================

it('allows/denies update based on state and user role', function (string $stateClass, string $userFn, bool $expected) {
    $actor = $userFn();
    $project = Project::factory()->by(user())->create(['state' => $stateClass::$name]);

    $this->actingAs($actor);
    expect($actor->can('update', $project))->toBe($expected);
})->with([
    // Draft — anyone can update
    'draft + user' => [Draft::class, 'user', true],
    'draft + cashOfficer' => [Draft::class, 'cashOfficer', true],
    'draft + budgetManager' => [Draft::class, 'budgetManager', true],

    // Applied — needs ref-finanzen-belege
    'applied + user' => [Applied::class, 'user', false],
    'applied + cashOfficer' => [Applied::class, 'cashOfficer', true],
    'applied + budgetManager' => [Applied::class, 'budgetManager', true],

    // NeedOrgApproval — needs ref-finanzen-hv
    'needOrgApproval + user' => [NeedOrgApproval::class, 'user', false],
    'needOrgApproval + cashOfficer' => [NeedOrgApproval::class, 'cashOfficer', false],
    'needOrgApproval + budgetManager' => [NeedOrgApproval::class, 'budgetManager', true],

    // ApprovedByOrg — needs ref-finanzen-hv
    'approvedByOrg + user' => [ApprovedByOrg::class, 'user', false],
    'approvedByOrg + cashOfficer' => [ApprovedByOrg::class, 'cashOfficer', false],
    'approvedByOrg + budgetManager' => [ApprovedByOrg::class, 'budgetManager', true],

    // NeedFinanceApproval — needs ref-finanzen-hv
    'needFinanceApproval + user' => [NeedFinanceApproval::class, 'user', false],
    'needFinanceApproval + cashOfficer' => [NeedFinanceApproval::class, 'cashOfficer', false],
    'needFinanceApproval + budgetManager' => [NeedFinanceApproval::class, 'budgetManager', true],

    // ApprovedByFinance — needs ref-finanzen-hv
    'approvedByFinance + user' => [ApprovedByFinance::class, 'user', false],
    'approvedByFinance + cashOfficer' => [ApprovedByFinance::class, 'cashOfficer', false],
    'approvedByFinance + budgetManager' => [ApprovedByFinance::class, 'budgetManager', true],

    // ApprovedByOther — needs ref-finanzen-hv
    'approvedByOther + user' => [ApprovedByOther::class, 'user', false],
    'approvedByOther + cashOfficer' => [ApprovedByOther::class, 'cashOfficer', false],
    'approvedByOther + budgetManager' => [ApprovedByOther::class, 'budgetManager', true],

    // Revoked — nobody
    'revoked + user' => [Revoked::class, 'user', false],
    'revoked + cashOfficer' => [Revoked::class, 'cashOfficer', false],
    'revoked + budgetManager' => [Revoked::class, 'budgetManager', false],

    // Terminated — nobody
    'terminated + user' => [Terminated::class, 'user', false],
    'terminated + cashOfficer' => [Terminated::class, 'cashOfficer', false],
    'terminated + budgetManager' => [Terminated::class, 'budgetManager', false],
]);

// =============================================================================
// transitionTo() — owner transitions
// =============================================================================
// user() owns the project and is in the org committee ('Students Council')
// Owner can transition to: Draft, Applied, Revoked, Terminated

it('allows owner to transition to permitted states', function ($fromState, string $toStateClass, bool $expected) {
    $project = Project::factory()->by(user())->withState($fromState)->create([

        'org' => 'Students Council',
    ]);
    $newState = new $toStateClass($project);

    $this->actingAs(user());
    expect(user()->can('transition-to', [$project, $newState]))->toBe($expected);
})->with([
    // Draft -> Applied (owner can apply)
    'draft -> applied' => [Draft::class, Applied::class, true],
    // Applied -> Draft (owner can retract)
    'applied -> draft' => [Applied::class, Draft::class, true],
    // Revoked -> Draft (owner can re-draft)
    'revoked -> draft' => [Revoked::class, Draft::class, true],
    // Applied -> Revoked (owner can revoke)
    'applied -> revoked' => [Applied::class, Revoked::class, true],
    // NeedOrgApproval -> Revoked (owner can revoke)
    'needOrgApproval -> revoked' => [NeedOrgApproval::class, Revoked::class, true],
    // NeedFinanceApproval -> Revoked (owner can revoke)
    'needFinanceApproval -> revoked' => [NeedFinanceApproval::class, Revoked::class, true],
    // ApprovedByOrg -> Terminated (owner can terminate)
    'approvedByOrg -> terminated' => [ApprovedByOrg::class, Terminated::class, true],
    // ApprovedByFinance -> Terminated (owner can terminate)
    'approvedByFinance -> terminated' => [ApprovedByFinance::class, Terminated::class, true],
    // ApprovedByOther -> Terminated (owner can terminate)
    'approvedByOther -> terminated' => [ApprovedByOther::class, Terminated::class, true],

    // Owner CANNOT approve (needs ref-finanzen-hv)
    'applied -> needOrgApproval' => [Applied::class, NeedOrgApproval::class, false],
    'applied -> needFinanceApproval' => [Applied::class, NeedFinanceApproval::class, false],
    'applied -> approvedByOrg' => [Applied::class, ApprovedByOrg::class, false],
    'applied -> approvedByFinance' => [Applied::class, ApprovedByFinance::class, false],
    'applied -> approvedByOther' => [Applied::class, ApprovedByOther::class, false],
]);

// =============================================================================
// transitionTo() — org member (non-owner) transitions
// =============================================================================
// user() is in 'Students Council' but does NOT own the project

it('allows org member (non-owner) to transition to permitted states', function (string $fromState, string $toStateClass, bool $expected) {
    // Project created by budgetManager, but org matches user()'s committee
    $project = Project::factory()->by(budgetManager())->create([
        'state' => $fromState,
        'org' => 'Students Council',
    ]);
    $newState = new $toStateClass($project);

    $this->actingAs(user());
    expect(user()->can('transition-to', [$project, $newState]))->toBe($expected);
})->with([
    // Org member can: Draft, Applied, Revoked, Terminated
    'applied -> draft' => [Applied::$name, Draft::class, true],
    'draft -> applied' => [Draft::$name, Applied::class, true],
    'applied -> revoked' => [Applied::$name, Revoked::class, true],
    'approvedByOrg -> terminated' => [ApprovedByOrg::$name, Terminated::class, true],

    // Org member CANNOT approve
    'applied -> needOrgApproval' => [Applied::$name, NeedOrgApproval::class, false],
    'applied -> approvedByOrg' => [Applied::$name, ApprovedByOrg::class, false],
]);

// =============================================================================
// transitionTo() — non-owner, non-org user has no transition rights
// =============================================================================

it('denies transitions for user with no ownership or org membership', function (string $fromState, string $toStateClass) {
    // Project not owned by user(), org doesn't match user()'s committees
    $project = Project::factory()->by(budgetManager())->create([
        'state' => $fromState,
        'org' => 'Financial Department',
    ]);
    $newState = new $toStateClass($project);

    $this->actingAs(user());
    expect(user()->can('transition-to', [$project, $newState]))->toBeFalse();
})->with([
    'applied -> draft' => [Applied::$name, Draft::class],
    'draft -> applied' => [Draft::$name, Applied::class],
    'applied -> revoked' => [Applied::$name, Revoked::class],
    'approvedByOrg -> terminated' => [ApprovedByOrg::$name, Terminated::class],
]);

// =============================================================================
// transitionTo() — cashOfficer (ref-finanzen-belege, no ref-finanzen-hv)
// =============================================================================
// Non-owner, non-org — only group-based permissions

it('allows cashOfficer to transition via ref-finanzen-belege', function (string $fromState, string $toStateClass, bool $expected) {
    $project = Project::factory()->by(user())->create([
        'state' => $fromState,
        'org' => 'Students Council',
    ]);
    $newState = new $toStateClass($project);

    $this->actingAs(cashOfficer());
    expect(cashOfficer()->can('transition-to', [$project, $newState]))->toBe($expected);
})->with([
    // financeAll targets: Draft, Applied, NeedOrgApproval, NeedFinanceApproval, Revoked
    'applied -> draft' => [Applied::$name, Draft::class, true],
    'draft -> applied' => [Draft::$name, Applied::class, true],
    'applied -> needOrgApproval' => [Applied::$name, NeedOrgApproval::class, true],
    'applied -> needFinanceApproval' => [Applied::$name, NeedFinanceApproval::class, true],
    'applied -> revoked' => [Applied::$name, Revoked::class, true],

    // cashOfficer CANNOT do ref-finanzen-hv targets
    'applied -> approvedByOrg' => [Applied::$name, ApprovedByOrg::class, false],
    'applied -> approvedByFinance' => [Applied::$name, ApprovedByFinance::class, false],
    'applied -> approvedByOther' => [Applied::$name, ApprovedByOther::class, false],
    'approvedByOrg -> terminated' => [ApprovedByOrg::$name, Terminated::class, false],
]);

// =============================================================================
// transitionTo() — budgetManager (ref-finanzen-hv + ref-finanzen-belege)
// =============================================================================

it('allows budgetManager to transition via ref-finanzen-hv', function (string $fromState, string $toStateClass, bool $expected) {
    $project = Project::factory()->by(user())->create([
        'state' => $fromState,
        'org' => 'Students Council',
    ]);
    $newState = new $toStateClass($project);

    $this->actingAs(budgetManager());
    expect(budgetManager()->can('transition-to', [$project, $newState]))->toBe($expected);
})->with([
    // budgetManager has both groups, can do everything on valid transitions
    'draft -> applied' => [Draft::$name, Applied::class, true],
    'applied -> draft' => [Applied::$name, Draft::class, true],
    'applied -> needOrgApproval' => [Applied::$name, NeedOrgApproval::class, true],
    'applied -> needFinanceApproval' => [Applied::$name, NeedFinanceApproval::class, true],
    'applied -> approvedByOrg' => [Applied::$name, ApprovedByOrg::class, true],
    'applied -> approvedByFinance' => [Applied::$name, ApprovedByFinance::class, true],
    'applied -> approvedByOther' => [Applied::$name, ApprovedByOther::class, true],
    'applied -> revoked' => [Applied::$name, Revoked::class, true],
    'approvedByOrg -> terminated' => [ApprovedByOrg::$name, Terminated::class, true],
    'approvedByFinance -> terminated' => [ApprovedByFinance::$name, Terminated::class, true],
    'approvedByOther -> terminated' => [ApprovedByOther::$name, Terminated::class, true],
]);

// =============================================================================
// transitionTo() — invalid transitions are denied regardless of permissions
// =============================================================================

it('denies structurally invalid transitions even for budgetManager', function (string $fromState, string $toStateClass) {
    $project = Project::factory()->by(budgetManager())->create([
        'state' => $fromState,
    ]);
    $newState = new $toStateClass($project);

    $this->actingAs(budgetManager());
    expect(budgetManager()->can('transition-to', [$project, $newState]))->toBeFalse();
})->with([
    // Draft can only go to Applied
    'draft -> terminated' => [Draft::$name, Terminated::class],
    'draft -> revoked' => [Draft::$name, Revoked::class],
    'draft -> needOrgApproval' => [Draft::$name, NeedOrgApproval::class],
    'draft -> approvedByOrg' => [Draft::$name, ApprovedByOrg::class],
    'draft -> approvedByFinance' => [Draft::$name, ApprovedByFinance::class],
    'draft -> approvedByOther' => [Draft::$name, ApprovedByOther::class],
    'draft -> needFinanceApproval' => [Draft::$name, NeedFinanceApproval::class],
    // Revoked can only go to Draft
    'revoked -> applied' => [Revoked::$name, Applied::class],
    'revoked -> terminated' => [Revoked::$name, Terminated::class],
    // Terminated cannot go anywhere except approval states (which are configured)
    // but cannot go to Draft or Applied
    'terminated -> draft' => [Terminated::$name, Draft::class],
    'terminated -> applied' => [Terminated::$name, Applied::class],
    'terminated -> revoked' => [Terminated::$name, Revoked::class],
]);
