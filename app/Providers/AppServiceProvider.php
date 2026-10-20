<?php

namespace App\Providers;

use App\Services\BrandService;
use App\Services\SettingsRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(BrandService::class);
    }

    public function boot(BrandService $brandService): void
    {
        // Production only speaks HTTPS (SPEC 17.2).
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        // Password rules (SPEC 5.1): 10 characters, mixed case and a number. Admins use 12 (PasswordRules).
        Password::defaults(fn () => \App\Support\PasswordRules::for());

        // An N+1 query is a bug everywhere except a live deployment (SPEC 9.1).
        Model::preventLazyLoading(! app()->isProduction());

        // Framework messages use the brand name (SPEC 11.6).
        config(['app.name' => $brandService->name()]);

        // One composer: layouts, mail layout, and the components that print the brand.
        View::composer(
            ['layouts.*', 'layouts::*', 'marketing.*', 'mail.layout', 'components.brand.*', 'components.site-header', 'components.site-footer', 'components.shell'],
            fn ($view) => $view->with('brand', app(BrandService::class)->brand()),
        );

        $this->limiters();

        Paginator::defaultView('pagination.links');
        Paginator::defaultSimpleView('pagination.links');
    }

    /** Named rate limiters (SPEC 17.2). The 429 page tells the person how long to wait. */
    private function limiters(): void
    {
        // Local testing aid, never in production (see config/security.php).
        if (config('security.rate_limits_off') && ! app()->isProduction()) {
            foreach (['register', 'forgot', 'sim', 'telemetry', 'public', 'admin', 'global', 'tiles', 'ledger', 'codes', 'resend'] as $name) {
                RateLimiter::for($name, fn () => Limit::none());
            }

            return;
        }

        $ip = fn (Request $r) => $r->ip() ?? 'unknown';
        $user = fn (Request $r) => $r->user()?->getAuthIdentifier() ?? $ip($r);

        RateLimiter::for('register', fn (Request $r) => Limit::perHour(5)->by($ip($r)));
        RateLimiter::for('forgot', fn (Request $r) => [
            Limit::perHour(5)->by('ip:'.$ip($r)),
            Limit::perHour(5)->by('email:'.sha1(strtolower((string) $r->input('email')))),
        ]);
        RateLimiter::for('sim', fn (Request $r) => Limit::perMinute(20)->by($user($r)));
        RateLimiter::for('telemetry', fn (Request $r) => Limit::perMinute(60)->by($user($r)));
        RateLimiter::for('public', fn (Request $r) => Limit::perMinute(120)->by($ip($r)));
        RateLimiter::for('admin', fn (Request $r) => Limit::perMinute(120)->by($user($r)));
        // The fallback for everything else. Map tiles, brand files and the two public feeds have limits of their own and a
        // single page view loads dozens of tiles, so they must not use up a visitor's allowance.
        // Map tiles come dozens to a page. The ledger feed refreshes itself. Code entry and resend are guess-limited.
        RateLimiter::for('tiles', fn (Request $r) => Limit::perMinute(900)->by($ip($r)));
        RateLimiter::for('ledger', fn (Request $r) => Limit::perMinute(30)->by($ip($r)));
        RateLimiter::for('codes', fn (Request $r) => Limit::perMinute(10)->by($user($r)));
        RateLimiter::for('resend', fn (Request $r) => Limit::perMinute(6)->by($user($r)));
        RateLimiter::for('global', fn (Request $r) => $r->is('map/tiles/*', 'brand/*', 'favicon.ico', 'site.webmanifest', 'public/*', 'livewire*')
            ? Limit::none()
            : Limit::perMinute(300)->by($ip($r)));
    }
}
