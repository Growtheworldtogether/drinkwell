<?php

use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\MachinesController;
use App\Http\Controllers\Admin\RecordsController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\HubController;
use App\Http\Controllers\OnboardController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFeedController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\TileController;
use App\Livewire\OnboardingWizard;
use Illuminate\Support\Facades\Route;

// Public pages.
Route::controller(PagesController::class)->middleware('throttle:public')->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/how-it-works', 'program')->name('program');
    Route::get('/your-earnings', 'earnings')->name('earnings');
    Route::get('/locations', 'locations')->name('locations');
    Route::get('/questions', 'questions')->name('questions');
    Route::get('/about', 'about')->name('about');
    Route::get('/terms', 'terms')->name('terms');
    Route::get('/privacy', 'privacy')->name('privacy');
});

Route::get('/public/machines', [PublicFeedController::class, 'machines'])->middleware('throttle:telemetry')->name('public.machines');
Route::get('/map/tiles/{z}/{x}/{y}.png', [TileController::class, 'show'])->whereNumber(['z', 'x', 'y'])->middleware('throttle:tiles')->name('map.tile');

Route::get('/favicon.ico', [BrandController::class, 'favicon']);
Route::get('/site.webmanifest', [BrandController::class, 'manifest']);
Route::get('/brand/icon/{size}', [BrandController::class, 'icon'])->whereNumber('size')->name('brand.icon');
Route::get('/brand/{variant}', [BrandController::class, 'logo']);

// Sends a signed-in user to their role home.
Route::get('/dashboard', fn () => redirect(auth()->user()->homeUrl() ?? '/'))
    ->middleware(['auth', 'active', 'verified.otp'])
    ->name('dashboard');

// Onboarding: sign-up details, age check, pairing, then the status page with the settlement instructions.
Route::middleware(['auth', 'active', 'verified.otp', 'role:applicant,partner'])->prefix('onboard')->group(function () {
    Route::livewire('/', OnboardingWizard::class)->name('onboard');
    Route::get('/status', [OnboardController::class, 'status'])->name('onboard.status');
    Route::post('/report', [OnboardController::class, 'report'])->name('onboard.report');
    Route::post('/demo-confirm', [OnboardController::class, 'demoConfirm'])->name('onboard.demo-confirm');
});

// The co-partner hub.
Route::middleware(['auth', 'active', 'verified.otp', 'role:partner'])->prefix('hub')->name('hub.')->group(function () {
    Route::get('/dashboard', [HubController::class, 'dashboard'])->name('dashboard');
    Route::get('/telemetry', [HubController::class, 'telemetry'])->middleware('throttle:telemetry')->name('telemetry');
    Route::get('/ledger.csv', [HubController::class, 'ledgerCsv'])->name('ledger.csv');
});

// Simulation commands: the owner of the machine, or an administrator.
Route::middleware(['auth', 'active', 'verified.otp', 'role:partner,admin', 'throttle:sim'])->prefix('sim')->name('sim.')->group(function () {
    Route::post('/{machine}/advance-day', [SimulationController::class, 'advanceDay'])->name('advance-day');
    Route::post('/{machine}/sales-burst', [SimulationController::class, 'salesBurst'])->name('sales-burst');
    Route::post('/{machine}/driver-restock', [SimulationController::class, 'driverRestock'])->name('driver-restock');
});

// Admin console.
Route::middleware(['admin.ip', 'auth', 'active', 'role:admin', 'admin.idle', 'throttle:admin'])->prefix('admin')->name('admin.')->group(function () {
    $m = MachinesController::class;
    Route::get('/', [$m, 'overview'])->name('overview');
    Route::get('/machines', [$m, 'index'])->name('machines');
    Route::get('/machines/{machine}', [$m, 'show'])->name('machines.show');
    Route::post('/ownerships/{ownership}/confirm', [$m, 'confirmDeposit'])->name('ownerships.confirm');

    Route::view('/settings', 'admin.settings.general')->name('settings');
    $brd = BrandingController::class;
    Route::get('/settings/branding', [$brd, 'show'])->name('settings.branding');
    Route::put('/settings/branding', [$brd, 'update'])->name('settings.branding.update');
    Route::post('/settings/branding/reset', [$brd, 'reset'])->middleware('password.confirm')->name('settings.branding.reset');

    $rec = RecordsController::class;
    Route::get('/audit-log', [$rec, 'audit'])->name('audit');
    Route::get('/email-log', [$rec, 'emails'])->name('emails');
    Route::post('/email-log/{log}/resend', [$rec, 'resend'])->name('emails.resend');
    Route::get('/integrity', [$rec, 'integrity'])->name('integrity');
    Route::post('/integrity', [$rec, 'run'])->name('integrity.check');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
