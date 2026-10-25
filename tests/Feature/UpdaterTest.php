<?php

use App\Enums\Role;
use App\Services\Updater;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/** A fake installed site in the shared-hosting layout: ~/drinkwell (app) beside ~/public_html (public files). */
function fakeSite(): array
{
    $home = sys_get_temp_dir().'/dw-upd-'.bin2hex(random_bytes(4));
    $base = $home.'/drinkwell';
    $public = $home.'/public_html';
    foreach (['app', 'vendor', 'storage/app', 'bootstrap/cache', 'resources/views'] as $d) {
        File::ensureDirectoryExists($base.'/'.$d);
    }
    File::ensureDirectoryExists($public.'/build');
    file_put_contents($base.'/artisan', 'old artisan');
    file_put_contents($base.'/vendor/autoload.php', '<?php // same');
    file_put_contents($base.'/app/Thing.php', 'old thing');
    file_put_contents($base.'/resources/views/page.blade.php', 'old page');
    file_put_contents($base.'/.env', 'APP_KEY=secret');
    file_put_contents($base.'/storage/app/upload.jpg', 'member upload');
    file_put_contents($base.'/version.json', json_encode(['tag' => 'release-20261020-1000']));
    file_put_contents($public.'/index.php', 'old index');
    file_put_contents($public.'/install.php', 'locked installer');
    file_put_contents($public.'/build/old.css', 'old css');

    return [$home, $base, $public];
}

/** A release package for that layout. @param array<string, string> $extra */
function fakePackage(string $tag = 'release-20261025-1200', array $extra = []): string
{
    $path = sys_get_temp_dir().'/dw-pkg-'.bin2hex(random_bytes(4)).'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $files = $extra + [
        'drinkwell/version.json' => json_encode(['tag' => $tag]),
        'drinkwell/artisan' => 'new artisan',
        'drinkwell/vendor/autoload.php' => '<?php // same',
        'drinkwell/app/Thing.php' => 'new thing',
        'drinkwell/app/NewThing.php' => 'brand new',
        'drinkwell/.env' => 'APP_KEY=from-package',
        'drinkwell/storage/app/upload.jpg' => 'should not overwrite',
        'public_html/index.php' => 'new index',
        'public_html/install.php' => 'fresh installer',
        'public_html/build/new.css' => 'new css',
    ];
    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
    }
    $zip->close();

    return $path;
}

it('installs a package: replaces and adds files, keeps settings, uploads and the installer, and can undo it', function () {
    [$home, $base, $public] = fakeSite();
    $u = new Updater($base, $public);
    expect($u->layout())->toBe('public_html')->and($u->assetName())->toBe('drinkwell-release-public_html.zip')
        ->and($u->current()['tag'])->toBe('release-20261020-1000');

    $result = $u->install(fakePackage());

    expect($result['to'])->toBe('release-20261025-1200')->and($result['unchanged'])->toBe(1)
        ->and(file_get_contents($base.'/app/Thing.php'))->toBe('new thing')
        ->and(file_get_contents($base.'/app/NewThing.php'))->toBe('brand new')
        ->and(file_get_contents($public.'/index.php'))->toBe('new index')
        ->and(file_get_contents($public.'/build/new.css'))->toBe('new css')
        ->and(file_get_contents($base.'/.env'))->toBe('APP_KEY=secret')
        ->and(file_get_contents($base.'/storage/app/upload.jpg'))->toBe('member upload')
        ->and(file_get_contents($public.'/install.php'))->toBe('locked installer')
        ->and($u->current()['tag'])->toBe('release-20261025-1200');

    $backup = $u->lastBackup();
    expect($backup['from'])->toBe('release-20261020-1000')->and($backup['to'])->toBe('release-20261025-1200');

    $u->restore($backup['path']);
    expect(file_get_contents($base.'/app/Thing.php'))->toBe('old thing')
        ->and(file_exists($base.'/app/NewThing.php'))->toBeFalse()
        ->and(file_get_contents($public.'/index.php'))->toBe('old index')
        ->and(file_exists($public.'/build/new.css'))->toBeFalse()
        ->and($u->current()['tag'])->toBe('release-20261020-1000')
        ->and($u->lastBackup()['restored_at'])->not->toBeNull();

    File::deleteDirectory($home);
});

it('puts everything back when an install fails part way', function () {
    [$home, $base, $public] = fakeSite();
    // A folder where the package wants a file makes the write fail after other files were already replaced.
    File::ensureDirectoryExists($base.'/zzz-blocker.php');
    $u = new Updater($base, $public);

    expect(fn () => $u->install(fakePackage(extra: ['drinkwell/zzz-blocker.php' => 'cannot land'])))->toThrow(RuntimeException::class);
    expect(file_get_contents($base.'/app/Thing.php'))->toBe('old thing')
        ->and(file_exists($base.'/app/NewThing.php'))->toBeFalse()
        ->and(file_get_contents($public.'/index.php'))->toBe('old index')
        ->and($u->current()['tag'])->toBe('release-20261020-1000');

    File::deleteDirectory($home);
});

it('refuses packages that do not fit or are unsafe', function () {
    [$home, $base, $public] = fakeSite();
    $u = new Updater($base, $public);

    $noVersion = sys_get_temp_dir().'/dw-bad-'.bin2hex(random_bytes(3)).'.zip';
    $z = new ZipArchive;
    $z->open($noVersion, ZipArchive::CREATE);
    $z->addFromString('drinkwell/artisan', 'x');
    $z->close();
    expect(fn () => $u->inspect($noVersion))->toThrow(RuntimeException::class, 'no version information');

    expect(fn () => $u->inspect(fakePackage(extra: ['drinkwell/../../evil.php' => 'x'])))->toThrow(RuntimeException::class, 'unsafe path');
    expect(fn () => $u->inspect(fakePackage('release-1'), 'release-2'))->toThrow(RuntimeException::class, 'not release-2');

    File::deleteDirectory($home);
});

it('checks GitHub, downloads with the fingerprint check, installs, and finishes from the admin page', function () {
    [$home, $base, $public] = fakeSite();
    $this->app->instance(Updater::class, new Updater($base, $public));
    $package = fakePackage();
    $bytes = file_get_contents($package);

    Http::fake([
        'api.github.com/repos/*/releases/latest' => Http::response(['tag_name' => 'release-20261025-1200', 'name' => 'Upload zip', 'published_at' => now()->toIso8601String(), 'body' => 'notes',
            'assets' => [['id' => 7, 'name' => 'drinkwell-release-public_html.zip', 'size' => strlen($bytes), 'digest' => 'sha256:'.hash('sha256', $bytes)]]]),
        'api.github.com/repos/*/releases/assets/7' => Http::response($bytes),
    ]);

    signedIn(Role::Admin);
    session(['auth.password_confirmed_at' => time()]);

    $this->post(route('admin.settings.system.check'))->assertSessionHas('status', fn ($s) => str_contains($s, 'new version is ready'));
    $this->get(route('admin.settings.system'))->assertSee('A new version is ready')->assertSee('Install update');

    $this->post(route('admin.settings.system.install'), ['developer_code' => 'nope'])->assertSessionHasErrors('developer_code', null, 'update');
    expect(file_get_contents($base.'/app/Thing.php'))->toBe('old thing');

    $go = $this->post(route('admin.settings.system.install'), ['developer_code' => 'DW-EMAIL-1453']);
    $go->assertRedirect()->assertCookie('laravel_maintenance');
    expect(file_get_contents($base.'/app/Thing.php'))->toBe('new thing')->and(app()->isDownForMaintenance())->toBeTrue();

    // The browser carries the "let the admin through" cookie from the install answer to the finish step.
    $bypass = collect($go->headers->getCookies())->first(fn ($c) => $c->getName() === 'laravel_maintenance');
    $this->withUnencryptedCookie('laravel_maintenance', $bypass->getValue())->get($go->headers->get('Location'))->assertRedirect(route('admin.settings.system'))
        ->assertSessionHas('status', fn ($s) => str_contains($s, 'Updated to release-20261025-1200'));
    expect(app()->isDownForMaintenance())->toBeFalse();

    $this->get(route('admin.settings.system'))->assertSee('Restore the previous version');

    // The file itself was asked for, not the release information (GitHub answers with whichever is asked).
    Http::assertSent(fn ($r) => str_contains($r->url(), '/releases/assets/7') && $r->header('Accept') === ['application/octet-stream']);
    File::deleteDirectory($home);
});

it('does not install a download that fails the fingerprint check', function () {
    [$home, $base, $public] = fakeSite();
    $this->app->instance(Updater::class, new Updater($base, $public));
    $bytes = file_get_contents(fakePackage());
    Http::fake([
        'api.github.com/repos/*/releases/latest' => Http::response(['tag_name' => 'release-20261025-1200', 'assets' => [['id' => 7, 'name' => 'drinkwell-release-public_html.zip', 'size' => 1, 'digest' => 'sha256:'.str_repeat('0', 64)]]]),
        'api.github.com/repos/*/releases/assets/7' => Http::response($bytes),
    ]);
    signedIn(Role::Admin);
    session(['auth.password_confirmed_at' => time()]);

    $this->post(route('admin.settings.system.install'), ['developer_code' => 'DW-EMAIL-1453'])
        ->assertSessionHas('error', fn ($s) => str_contains($s, 'fingerprint'));
    expect(file_get_contents($base.'/app/Thing.php'))->toBe('old thing')->and(app()->isDownForMaintenance())->toBeFalse();
    File::deleteDirectory($home);
});
