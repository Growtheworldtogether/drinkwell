<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\BrandAssets;
use App\Services\BrandService;
use App\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Admin, Settings, Branding (SPEC 11.6). One brand per installation; a save changes it everywhere at the next page load. */
class BrandingController extends Controller
{
    private const FILE_KEYS = ['brand.logo_light_path', 'brand.logo_dark_path', 'brand.favicon_path'];

    private const TEXT_KEYS = ['brand.name', 'brand.tagline', 'support_email'];

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly BrandAssets $assets,
        private readonly BrandService $brand,
    ) {}

    public function show(): View
    {
        return view('admin.settings.branding', [
            'brand' => $this->brand->brand(),
            'favicon' => $this->settings->nullableString('brand.favicon_path'),
            'defaults' => config('drinkwell.brand'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:40', fn ($a, $v, $fail) => ($v !== strip_tags((string) $v)) ? $fail('The name cannot contain HTML.') : null],
            'tagline' => ['nullable', 'string', 'max:80', fn ($a, $v, $fail) => ($v !== strip_tags((string) $v)) ? $fail('The tagline cannot contain HTML.') : null],
            'support_email' => ['required', 'email:rfc', 'max:190'],
            'logo_light' => ['nullable', 'file'],
            'logo_dark' => ['nullable', 'file'],
            'favicon' => ['nullable', 'file'],
        ], [
            'name.required' => 'Enter the platform name, 1 to 40 characters.',
            'name.max' => 'The name is longer than 40 characters. Shorten it.',
            'tagline.max' => 'The tagline is longer than 80 characters. Shorten it.',
            'support_email.required' => 'Enter a support email address.',
            'support_email.email' => 'Enter a valid email address, like help@example.com.',
        ]);

        $name = trim((string) $request->input('name'));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Enter the platform name, 1 to 40 characters.']);
        }

        // Process every upload before anything is written, so a bad file saves nothing.
        $new = [];
        $errors = [];
        foreach (['logo_light' => 'brand.logo_light_path', 'logo_dark' => 'brand.logo_dark_path', 'favicon' => 'brand.favicon_path'] as $field => $key) {
            if (! $request->hasFile($field)) {
                continue;
            }
            try {
                $new[$key] = $field === 'favicon' ? $this->assets->storeFavicon($request->file($field), $field) : $this->assets->storeLogo($request->file($field), $field);
            } catch (ValidationException $e) {
                $errors += $e->errors();
            }
        }
        if ($errors !== []) {
            foreach ($new as $key => $path) {
                $this->assets->delete($path);
            }
            throw ValidationException::withMessages($errors);
        }

        // Remove checkboxes clear a file without replacing it.
        foreach (['remove_logo_light' => 'brand.logo_light_path', 'remove_logo_dark' => 'brand.logo_dark_path', 'remove_favicon' => 'brand.favicon_path'] as $flag => $key) {
            if ($request->boolean($flag) && ! array_key_exists($key, $new)) {
                $new[$key] = null;
            }
        }

        $before = $this->snapshot();
        $text = [
            'brand.name' => $name,
            'brand.tagline' => filled($request->input('tagline')) ? trim((string) $request->input('tagline')) : null,
            'support_email' => trim((string) $request->input('support_email')),
        ];

        foreach ($text + $new as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $request->user()->id]);
        }
        Setting::query()->updateOrCreate(['key' => 'brand.updated_at'], ['value' => now()->toIso8601String(), 'updated_by' => $request->user()->id]);
        $this->settings->flush();

        // The old files go only after the new settings are saved.
        foreach ($new as $key => $path) {
            if (($before[$key] ?? null) && $before[$key] !== $path) {
                $this->assets->delete($before[$key]);
            }
        }

        AuditLogger::log($request->user(), 'brand_updated', null, $before, $this->snapshot());

        return redirect()->route('admin.settings.branding')->with('status', 'Branding saved. It shows everywhere from the next page load.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $before = $this->snapshot();

        foreach (self::FILE_KEYS as $key) {
            $this->assets->delete($before[$key] ?? null);
        }

        Setting::query()->whereIn('key', [...self::TEXT_KEYS, ...self::FILE_KEYS])->delete();
        Setting::query()->updateOrCreate(['key' => 'brand.updated_at'], ['value' => now()->toIso8601String(), 'updated_by' => $request->user()->id]);
        $this->settings->flush();

        AuditLogger::log($request->user(), 'brand_reset', null, $before, $this->snapshot());

        return redirect()->route('admin.settings.branding')->with('status', 'Branding reset to the defaults.');
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $this->settings->flush();

        return [
            'brand.name' => $this->brand->name(),
            'brand.tagline' => $this->brand->tagline(),
            'support_email' => $this->brand->supportEmail(),
            'brand.logo_light_path' => $this->settings->nullableString('brand.logo_light_path'),
            'brand.logo_dark_path' => $this->settings->nullableString('brand.logo_dark_path'),
            'brand.favicon_path' => $this->settings->nullableString('brand.favicon_path'),
        ];
    }
}
