<?php

use Illuminate\Support\Facades\File;

/**
 * The command is exercised against a throwaway doms directory (--doms-dir) laid out the way
 * Hostsharing does it: ~/doms/<domain>/{htdocs-ssl,fastcgi-ssl,fastcgi}, with htdocs-ssl a
 * symlink to the document root it serves.
 */
beforeEach(function (): void {
    $this->domsDir = base_path('storage/framework/testing/doms-'.uniqid());

    // A domain whose document root is this checkout - the default run should target it.
    File::ensureDirectoryExists($this->domsDir.'/stufis.example/fastcgi-ssl');
    File::ensureDirectoryExists($this->domsDir.'/stufis.example/fastcgi');
    symlink(public_path(), $this->domsDir.'/stufis.example/htdocs-ssl');

    // An unrelated domain on the same webspace - it must be left alone unless asked for.
    File::ensureDirectoryExists($this->domsDir.'/other.example/fastcgi-ssl');
    File::ensureDirectoryExists($this->domsDir.'/other.example/htdocs-ssl-target');
    symlink($this->domsDir.'/other.example/htdocs-ssl-target', $this->domsDir.'/other.example/htdocs-ssl');
});

afterEach(function (): void {
    File::deleteDirectory($this->domsDir);
});

function templateContents(): string
{
    return File::get(base_path('bin/templates/php.ini'));
}

it('installs the template only into domains serving this instance', function (): void {
    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir, '--force' => true])
        ->assertSuccessful();

    expect($this->domsDir.'/stufis.example/fastcgi-ssl/php.ini')->toBeReadableFile()
        ->and(File::get($this->domsDir.'/stufis.example/fastcgi-ssl/php.ini'))->toBe(templateContents())
        ->and(File::exists($this->domsDir.'/other.example/fastcgi-ssl/php.ini'))->toBeFalse();
});

it('skips a fastcgi directory whose document root is absent', function (): void {
    // stufis.example has htdocs-ssl but no plain htdocs, so only the ssl side is configured.
    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir, '--force' => true])
        ->assertSuccessful();

    expect(File::exists($this->domsDir.'/stufis.example/fastcgi/php.ini'))->toBeFalse();
});

it('covers every domain with --all', function (): void {
    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir, '--all' => true, '--force' => true])
        ->assertSuccessful();

    expect(File::get($this->domsDir.'/other.example/fastcgi-ssl/php.ini'))->toBe(templateContents())
        ->and(File::get($this->domsDir.'/stufis.example/fastcgi/php.ini'))->toBe(templateContents());
});

it('targets a named domain even when it does not serve this instance', function (): void {
    $this->artisan('stufis:php-ini', [
        '--doms-dir' => $this->domsDir,
        '--domain' => ['other.example'],
        '--force' => true,
    ])->assertSuccessful();

    expect(File::get($this->domsDir.'/other.example/fastcgi-ssl/php.ini'))->toBe(templateContents())
        ->and(File::exists($this->domsDir.'/stufis.example/fastcgi-ssl/php.ini'))->toBeFalse();
});

it('writes nothing on a dry run', function (): void {
    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir, '--dry-run' => true])
        ->assertSuccessful();

    expect(File::exists($this->domsDir.'/stufis.example/fastcgi-ssl/php.ini'))->toBeFalse();
});

it('backs up a php.ini it overwrites', function (): void {
    $target = $this->domsDir.'/stufis.example/fastcgi-ssl/php.ini';
    File::put($target, "memory_limit = 64M\n");

    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir, '--force' => true])
        ->assertSuccessful();

    $backups = File::glob($target.'.bak-*');

    expect($backups)->toHaveCount(1)
        ->and(File::get($backups[0]))->toBe("memory_limit = 64M\n")
        ->and(File::get($target))->toBe(templateContents());
});

it('reports an already matching php.ini as unchanged and leaves no backup', function (): void {
    $target = $this->domsDir.'/stufis.example/fastcgi-ssl/php.ini';
    File::put($target, templateContents());

    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir, '--force' => true])
        ->expectsOutputToContain('nothing to do')
        ->assertSuccessful();

    expect(File::glob($target.'.bak-*'))->toBeEmpty();
});

it('fails when no domain serves this instance', function (): void {
    File::deleteDirectory($this->domsDir.'/stufis.example');

    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir, '--force' => true])
        ->assertFailed();
});

it('fails when the doms directory does not exist', function (): void {
    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir.'/nope', '--force' => true])
        ->assertFailed();
});

it('leaves everything alone when the confirmation is declined', function (): void {
    $this->artisan('stufis:php-ini', ['--doms-dir' => $this->domsDir])
        ->expectsConfirmation('Write 1 file(s)?', 'no')
        ->assertFailed();

    expect(File::exists($this->domsDir.'/stufis.example/fastcgi-ssl/php.ini'))->toBeFalse();
});
