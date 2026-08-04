<?php

namespace Tests\Pest\Auth;

use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\OidcAuthService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Jumbojett\OpenIDConnectClientException;

uses(DatabaseTransactions::class);

/**
 * Insert a fake session row directly (the endpoint works on the sessions table,
 * not the live session store, which uses the array driver under test).
 */
function seedSession(string $id, ?string $oidcSid = null, ?int $userId = null): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'payload' => base64_encode('x'),
        'last_activity' => 1700000000,
        'oidc_sid' => $oidcSid,
    ]);
}

/**
 * Bind a partially-mocked OidcAuthService as the active auth service: real
 * backChannelLogout()/destroySessions() run, but token verification is stubbed
 * (jumbojett needs a real signed token + live JWKS, out of reach in a unit test).
 */
function fakeOidcResolving(array $result): void
{
    $service = \Mockery::mock(OidcAuthService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('verifyBackChannelLogoutToken')->andReturn($result);

    app()->instance(AuthService::class, $service);
}

it('destroys the session matching the logout token sid', function (): void {
    fakeOidcResolving(['sid' => 'sid-target', 'sub' => null]);
    seedSession('keep-me', oidcSid: 'sid-other');
    seedSession('kill-me', oidcSid: 'sid-target');

    $response = $this->post(route('logout.backchannel'), ['logout_token' => 'ignored-by-mock']);

    $response->assertOk();
    expect(DB::table('sessions')->where('id', 'kill-me')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'keep-me')->exists())->toBeTrue();
});

it('falls back to sub and clears every session of that user when no sid is given', function (): void {
    $user = User::factory()->create(['provider' => 'oidc', 'provider_uid' => 'ldap-uid-9']);
    fakeOidcResolving(['sid' => null, 'sub' => 'ldap-uid-9']);
    seedSession('device-a', userId: $user->id);
    seedSession('device-b', userId: $user->id);
    seedSession('other-user', userId: null);

    $response = $this->post(route('logout.backchannel'), ['logout_token' => 'ignored']);

    $response->assertOk();
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('id', 'other-user')->exists())->toBeTrue();
});

it('returns 400 and keeps sessions when the logout token is invalid', function (): void {
    $service = \Mockery::mock(OidcAuthService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('verifyBackChannelLogoutToken')
        ->andThrow(new OpenIDConnectClientException('bad token'));
    app()->instance(AuthService::class, $service);
    seedSession('survivor', oidcSid: 'sid-x');

    $response = $this->post(route('logout.backchannel'), ['logout_token' => 'garbage']);

    $response->assertStatus(400);
    expect(DB::table('sessions')->where('id', 'survivor')->exists())->toBeTrue();
});

it('is not available when the active auth service does not support it', function (): void {
    // A non-OIDC service falls through to AuthService's default (404).
    app()->instance(AuthService::class, \Mockery::mock(AuthService::class)->makePartial());

    $this->post(route('logout.backchannel'), ['logout_token' => 'x'])
        ->assertNotFound();
});
