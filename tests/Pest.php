<?php

use App\Models\User;
use framework\Singleton;
use Illuminate\Http\Testing\File;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Pest');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @return User returns a default user without special permissions
 */
function userNoLogin(): User
{
    return User::where(['username' => 'user-no-login'])->first();
}

/**
 * @return User returns a default user without special permissions
 */
function user(): User
{
    return User::where(['username' => 'user'])->first();
}

/**
 * @return User returns a budget manager user, with budget manager permissions
 */
function budgetManager(): User
{
    return User::where(['username' => 'hhv'])->first();
}

/**
 * @return User returns a cash management user, with cash manager permissions
 */
function cashOfficer(): User
{
    return User::where(['username' => 'kv'])->first();
}

/**
 * @return User returns a admin user, with admin permissions
 */
function adminUser(): User
{
    return User::where(['username' => 'admin'])->first();
}

/*
 * The session token legacyPost() pins. Legacy pages compare a posted `nonce` against
 * csrf_token() themselves - the legacy route group runs without Laravel's CSRF middleware -
 * so the token has to be known in advance rather than read out of the session.
 */
const LEGACY_NONCE = 'valid-test-nonce';

/**
 * Posts to a legacy page.
 *
 * `Renderer` builds its own request with `Request::createFromGlobals()` rather than taking
 * Laravel's - deliberately, because the global middleware trims strings and a trimmed bank
 * PIN is indistinguishable from a wrong one. The upshot for a test is that the superglobals
 * have to carry the request too, or the legacy side sees the CLI's GET with an empty body.
 */
function legacyPost(TestCase $test, string $uri, array $data): TestResponse
{
    // Reached through $GLOBALS rather than the superglobals directly: Rector rewrites a plain
    // `$_POST` read into the Request facade, which is the very thing the legacy side does not
    // look at.
    $serverBefore = $GLOBALS['_SERVER'];
    $postBefore = $GLOBALS['_POST'] ?? [];

    $GLOBALS['_SERVER']['REQUEST_METHOD'] = 'POST';
    $GLOBALS['_SERVER']['REQUEST_URI'] = $uri;
    $GLOBALS['_POST'] = $data;

    try {
        return $test->withSession(['_token' => LEGACY_NONCE])->post($uri, $data);
    } finally {
        $GLOBALS['_SERVER'] = $serverBefore;
        $GLOBALS['_POST'] = $postBefore;
    }
}

/**
 * The legacy document out of a response.
 *
 * Legacy pages are handed to the browser inside the `srcdoc` of an iframe (see
 * resources/views/legacy/main.blade.php), so their markup arrives htmlspecialchars-encoded and
 * an assertion on a tag or an attribute would never match the raw response body. Text still
 * matches either way - this is for the markup.
 */
function legacyHtml(TestResponse $response): string
{
    // htmlspecialchars turns every `"` into `&quot;`, so the attribute cannot end early.
    if (preg_match('/srcdoc="([^"]*)"/s', (string) $response->getContent(), $match) !== 1) {
        return (string) $response->getContent();
    }

    return html_entity_decode($match[1], ENT_QUOTES);
}

/**
 * Drops the legacy singletons, DBConnector above all.
 *
 * It grabs `DB::getPdo()` once and keeps it for the life of the process. Rolling back a test
 * makes Laravel reconnect, so that cached handle goes stale and the legacy side stops seeing
 * anything the test wrote - which shows up as "this bank access does not exist" from the
 * second test onwards. A real request never notices: one process, one request, one handle.
 */
function resetLegacySingletons(): void
{
    if (! class_exists(Singleton::class, false)) {
        // inc.all.php has not been pulled in yet, so there is nothing to reset.
        return;
    }

    new ReflectionProperty(Singleton::class, 'instances')->setValue(null, []);
}

/**
 * @return File the by livewire expected filetype
 */
function testFile(string $storage_path, ?string $fileName = null): File
{
    if ($fileName === null) {
        $fileName = str($storage_path)->explode('/')->last();
    }
    $content = Storage::disk('tests')->get($storage_path);

    return File::createWithContent($fileName, $content);
}
