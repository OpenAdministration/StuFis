<?php

namespace Tests\Pest\Auth;

use App\Services\Auth\OidcAuthService;
use App\Services\Auth\OidcClient;

/**
 * Parse the query string of the URL afterLogout() redirects to.
 */
function logoutRedirectQuery(?string $endSessionEndpoint, array $config = []): array
{
    config(array_merge([
        'services.oidc.client_id' => 'stufis-client',
        'services.oidc.post_logout_redirect' => null,
        'services.oidc.logout_confirm' => false,
    ], $config));

    $client = \Mockery::mock(OidcClient::class);
    $client->shouldReceive('endSessionEndpoint')->andReturn($endSessionEndpoint);

    session(['oidc.id_token' => 'THE_ID_TOKEN']);

    $response = new OidcAuthService($client)->afterLogout();

    $target = $response->getTargetUrl();
    parse_str((string) parse_url($target, PHP_URL_QUERY), $query);

    return ['url' => $target, 'query' => $query];
}

it('redirects to the IdP end_session_endpoint with id_token_hint and post_logout_redirect_uri', function (): void {
    ['url' => $url, 'query' => $query] = logoutRedirectQuery('https://idp.example/logout');

    expect($url)->toStartWith('https://idp.example/logout?')
        ->and($query['id_token_hint'])->toBe('THE_ID_TOKEN')
        ->and($query['post_logout_redirect_uri'])->toBe(route('login'))
        ->and($query)->not->toHaveKey('client_id');
});

it('uses the configured post-logout redirect target when set', function (): void {
    ['query' => $query] = logoutRedirectQuery(
        'https://idp.example/logout',
        ['services.oidc.post_logout_redirect' => 'https://stufis.example/bye'],
    );

    expect($query['post_logout_redirect_uri'])->toBe('https://stufis.example/bye');
});

it('omits id_token_hint and sends client_id when logout confirmation is requested', function (): void {
    ['query' => $query] = logoutRedirectQuery(
        'https://idp.example/logout',
        ['services.oidc.logout_confirm' => true],
    );

    expect($query)->not->toHaveKey('id_token_hint')
        ->and($query['client_id'])->toBe('stufis-client');
});

it('falls back to a local redirect when the IdP advertises no logout endpoint', function (): void {
    ['url' => $url] = logoutRedirectQuery(null);

    expect($url)->toBe(route('login'));
});
