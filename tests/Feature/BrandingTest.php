<?php

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\BrandService;
use App\Services\SettingsRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
    $this->save = fn (array $extra = []) => $this->actingAs($this->admin)->put(route('admin.settings.branding.update'), $extra + [
        'name' => 'Fleet Co', 'tagline' => 'Own a third of a car.', 'support_email' => 'help@fleet.example',
    ]);
});

it('keeps branding away from guests, investors and applicants', function () {
    $this->get(route('admin.settings.branding'))->assertRedirect();
    $this->actingAs(User::factory()->partner()->create())->get(route('admin.settings.branding'))->assertForbidden();
    $this->actingAs(User::factory()->partner()->create())->put(route('admin.settings.branding.update'), ['name' => 'X'])->assertForbidden();
    $this->actingAs(User::factory()->create())->post(route('admin.settings.branding.reset'))->assertForbidden();
});

it('shows the branding page with its tabs and live preview', function () {
    $this->actingAs($this->admin)->get(route('admin.settings.branding'))->assertOk()->assertSee('Live preview')->assertSee('Logo for light backgrounds')->assertSee('Reset to defaults');
    $this->actingAs($this->admin)->get(route('admin.settings'))->assertOk()->assertSee('Branding');
});

it('saves the text fields and the brand shows everywhere at the next load', function () {
    ($this->save)()->assertRedirect(route('admin.settings.branding'));

    $brand = app(BrandService::class);
    expect($brand->name())->toBe('Fleet Co')->and($brand->tagline())->toBe('Own a third of a car.')->and($brand->supportEmail())->toBe('help@fleet.example')
        ->and(config('app.name'))->not->toBeNull();

    // Tab title, wordmark, description, footer.
    $home = $this->get('/')->assertOk();
    $home->assertSee('· Fleet Co', false)->assertSee('Own a third of a car.', false)->assertSee('help@fleet.example', false);
    $this->get('/terms')->assertSee('help@fleet.example', false)->assertSee('Fleet Co');
    $this->get('/site.webmanifest')->assertJsonPath('name', 'Fleet Co');
    $this->actingAs($this->admin)->get(route('admin.overview'))->assertSee('Overview · Fleet Co', false);

    // Mail subject, sender name, header and footer.
    $html = (new \App\Mail\PasswordChanged('Ada', 'now'))->render();
    expect($html)->toContain('Fleet Co')->toContain('help@fleet.example');
    expect((new \App\Mail\PasswordChanged('Ada', 'now'))->subjectText())->toStartWith('Fleet Co: ');
});

it('audits a save with before and after values', function () {
    ($this->save)();

    $row = AdminAuditLog::where('action', 'brand_updated')->sole();
    expect($row->before['brand.name'])->toBe(config('drinkwell.brand.name'))->and($row->after['brand.name'])->toBe('Fleet Co')
        ->and($row->after['support_email'])->toBe('help@fleet.example');
});

it('validates the text fields and states the fix', function () {
    ($this->save)(['name' => ''])->assertSessionHasErrors('name');
    ($this->save)(['name' => str_repeat('a', 41)])->assertSessionHasErrors('name');
    ($this->save)(['name' => '<b>Bold</b>'])->assertSessionHasErrors('name');
    ($this->save)(['tagline' => str_repeat('t', 81)])->assertSessionHasErrors('tagline');
    ($this->save)(['support_email' => 'not-an-email'])->assertSessionHasErrors('support_email');

    expect(AdminAuditLog::where('action', 'brand_updated')->count())->toBe(0)->and(app(BrandService::class)->name())->toBe(config('drinkwell.brand.name'));
});

it('stores a logo re-encoded as a PNG under a random name and uses it', function () {
    ($this->save)(['logo_light' => UploadedFile::fake()->image('my company logo.png', 600, 200)])->assertSessionHasNoErrors();

    $path = app(SettingsRepository::class)->string('brand.logo_light_path');
    expect($path)->toStartWith('brand/')->toEndWith('.png')->not->toContain('company');
    Storage::disk('public')->assertExists($path);

    $info = getimagesizefromstring(Storage::disk('public')->get($path));
    expect($info[0])->toBe(600)->and($info['mime'])->toBe('image/png');

    $this->get('/admin')->assertSee(app(BrandService::class)->logoUrl('light'), false);
});

it('refuses SVG, a narrow logo, an oversized logo and a non-image', function () {
    $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    ($this->save)(['logo_light' => $svg])->assertSessionHasErrors('logo_light');
    expect(session('errors')->first('logo_light'))->toContain('SVG');

    ($this->save)(['logo_light' => UploadedFile::fake()->image('a.png', 180, 60)])->assertSessionHasErrors('logo_light');
    expect(session('errors')->first('logo_light'))->toContain('180 px wide')->toContain('at least 240 px');

    ($this->save)(['logo_dark' => UploadedFile::fake()->createWithContent('b.png', str_repeat('x', 2048))])->assertSessionHasErrors('logo_dark');
    ($this->save)(['logo_dark' => UploadedFile::fake()->create('c.pdf', 20, 'application/pdf')])->assertSessionHasErrors('logo_dark');

    $big = UploadedFile::fake()->image('d.png', 2400, 2400);
    ($this->save)(['logo_dark' => $big]);
    $dark = app(SettingsRepository::class)->nullableString('brand.logo_dark_path');
    if ($dark) {
        expect(getimagesizefromstring(Storage::disk('public')->get($dark))[0])->toBeLessThanOrEqual(2000);
    }
    expect(Storage::disk('public')->allFiles('brand'))->each->not->toContain('.svg');
});

it('saves nothing when one of several uploads is bad', function () {
    ($this->save)(['logo_light' => UploadedFile::fake()->image('ok.png', 400, 100), 'logo_dark' => UploadedFile::fake()->image('bad.png', 100, 40)])->assertSessionHasErrors('logo_dark');

    expect(Storage::disk('public')->allFiles('brand'))->toBe([])->and(app(SettingsRepository::class)->nullableString('brand.logo_light_path'))->toBeNull()
        ->and(app(BrandService::class)->name())->toBe(config('drinkwell.brand.name'));
});

it('deletes the old logo after saving a new one', function () {
    ($this->save)(['logo_light' => UploadedFile::fake()->image('one.png', 400, 100)]);
    $first = app(SettingsRepository::class)->string('brand.logo_light_path');

    ($this->save)(['logo_light' => UploadedFile::fake()->image('two.png', 500, 100)]);
    $second = app(SettingsRepository::class)->string('brand.logo_light_path');

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('uses the text wordmark on dark surfaces when only a light logo exists', function () {
    ($this->save)(['logo_light' => UploadedFile::fake()->image('l.png', 400, 100)]);

    $brand = app(BrandService::class);
    expect($brand->logoUrl('light'))->not->toBeNull()->and($brand->logoUrl('dark'))->toBeNull();

    $html = view('components.brand.logo', ['variant' => 'dark'] + ['brand' => $brand->brand()])->render();
    expect($html)->toContain('text-af-white')->toContain('Fleet Co')->not->toContain('<img');
});

it('derives every favicon size from one square PNG and serves them', function () {
    ($this->save)(['favicon' => UploadedFile::fake()->image('fav.png', 600, 600)])->assertSessionHasNoErrors();

    foreach ([16, 32, 180, 192, 512] as $size) {
        $r = $this->get("/brand/icon/{$size}")->assertOk()->assertHeader('Content-Type', 'image/png');
        expect(getimagesizefromstring($r->getContent())[0])->toBe($size);
    }
    $this->get('/favicon.ico')->assertOk();
    $this->get('/')->assertSee('/brand/icon/32?v=', false);
    $this->get('/site.webmanifest')->assertJsonPath('icons.1.sizes', '512x512');
});

it('refuses a favicon that is not square, too small, or not a PNG', function () {
    ($this->save)(['favicon' => UploadedFile::fake()->image('a.png', 600, 520)])->assertSessionHasErrors('favicon');
    ($this->save)(['favicon' => UploadedFile::fake()->image('b.png', 200, 200)])->assertSessionHasErrors('favicon');
    ($this->save)(['favicon' => UploadedFile::fake()->image('ok.png', 500, 500)])->assertSessionHasNoErrors();
    ($this->save)(['favicon' => UploadedFile::fake()->image('c.jpg', 600, 600)])->assertSessionHasErrors('favicon');
});

it('falls back to the built-in mark with no favicon, and removing one restores it', function () {
    $default = $this->get('/brand/icon/32')->assertOk()->getContent();

    ($this->save)(['favicon' => UploadedFile::fake()->image('fav.png', 600, 600)]);
    $token = app(SettingsRepository::class)->string('brand.favicon_path');
    expect(Storage::disk('public')->allFiles('brand/icons'))->toHaveCount(5);

    ($this->save)(['remove_favicon' => 1]);

    expect(app(SettingsRepository::class)->nullableString('brand.favicon_path'))->toBeNull()->and(Storage::disk('public')->allFiles('brand/icons'))->toBe([])
        ->and($this->get('/brand/icon/32')->getContent())->toBe($default);
});

it('resets to the defaults, deletes the files and audits it', function () {
    ($this->save)(['logo_light' => UploadedFile::fake()->image('l.png', 400, 100), 'favicon' => UploadedFile::fake()->image('f.png', 600, 600)]);

    $this->actingAs($this->admin)->post(route('admin.settings.branding.reset'))->assertRedirect(route('admin.settings.branding'));

    $brand = app(BrandService::class);
    expect($brand->name())->toBe(config('drinkwell.brand.name'))->and($brand->logoUrl('light'))->toBeNull()
        ->and(Storage::disk('public')->allFiles('brand'))->toBe([])
        ->and(AdminAuditLog::where('action', 'brand_reset')->count())->toBe(1);
});

it('changes the cache-busting version on every save', function () {
    ($this->save)();
    $v1 = app(BrandService::class)->version();
    $this->travel(2)->seconds();
    ($this->save)(['name' => 'Fleet Co 2']);

    expect(app(BrandService::class)->version())->not->toBe($v1);
});

it('replaces the platform name everywhere it is written, including inside sentences', function () {
    ($this->save)(['name' => 'Zephyr Rides'])->assertSessionHasNoErrors();
    auth()->logout();

    $pages = ['/', '/how-it-works', '/terms', '/privacy', '/login', '/register', '/forgot-password', '/site.webmanifest'];
    foreach ($pages as $uri) {
        $html = $this->get($uri)->assertOk()->getContent();
        expect($html)->toContain('Zephyr Rides')
            ->and(stripos(preg_replace('~drinkwell:~i', '', $html), 'drinkwell'))->toBeFalse("Old name still on {$uri}");
    }

    $this->actingAs(User::factory()->partner()->create());
    foreach (['profile.edit'] as $name) {
        $html = $this->get(route($name))->getContent();
        expect(stripos($html, 'drinkwell'))->toBeFalse("Old name still on {$name}");
    }

    $this->actingAs($this->admin);
    foreach (['admin.overview', 'admin.machines', 'admin.settings', 'admin.emails', 'admin.integrity'] as $name) {
        expect(stripos($this->get(route($name))->getContent(), 'drinkwell'))->toBeFalse("Old name still on {$name}");
    }
});
