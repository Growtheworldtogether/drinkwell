<?php

namespace App\Livewire;

use App\Enums\Role;
use App\Services\AuditLogger;
use App\Services\MailDispatcher;
use App\Services\OtpService;
use App\Services\SettingsRepository;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** Settings, General tab: sender name, admin emails and the read-only code values. Branding is its own page. */
class AdminSettings extends Component
{
    /** @var array<string, array{on: bool, email: string}> */
    public array $notify = [];

    public string $senderName = '';

    public string $notice = '';

    public function mount(SettingsRepository $settings): void
    {
        $this->authorizeAdmin();
        $this->senderName = $settings->string('mail.from_name');

        foreach (array_keys(MailDispatcher::ADMIN_TOPICS) as $topic) {
            $this->notify[$topic] = [
                'on' => $settings->bool("notify.{$topic}.enabled", true),
                'email' => $settings->string("notify.{$topic}.email"),
            ];
        }
    }

    public function saveSender(SettingsRepository $settings): void
    {
        $this->authorizeAdmin();

        $this->senderName = trim($this->senderName);
        $this->validate([
            'senderName' => ['nullable', 'string', 'max:60', fn ($a, $v, $fail) => ($v !== strip_tags((string) $v) || preg_match('/[<>"\r\n]/', (string) $v)) ? $fail('The name cannot contain HTML, quotes or line breaks.') : null],
        ], ['senderName.max' => 'The sender name is longer than 60 characters. Shorten it.'], ['senderName' => 'sender name']);

        $settings->set('mail.from_name', $this->senderName === '' ? '' : $this->senderName, auth()->user());

        $this->notice = $this->senderName === ''
            ? 'Emails will show the platform name as the sender.'
            : 'Emails will show "'.$this->senderName.'" as the sender.';
    }

    public function saveNotify(SettingsRepository $settings): void
    {
        $this->authorizeAdmin();

        $rules = [];
        foreach (array_keys(MailDispatcher::ADMIN_TOPICS) as $topic) {
            $this->notify[$topic]['on'] = (bool) ($this->notify[$topic]['on'] ?? false);
            $this->notify[$topic]['email'] = strtolower(trim((string) ($this->notify[$topic]['email'] ?? '')));
            $rules["notify.{$topic}.email"] = ['nullable', 'email:rfc', 'max:190'];
        }

        $this->validate($rules, ['*.email' => 'Enter a valid email address.'], array_combine(
            array_map(fn ($t) => "notify.{$t}.email", array_keys(MailDispatcher::ADMIN_TOPICS)),
            array_map(fn ($t) => 'email', array_keys(MailDispatcher::ADMIN_TOPICS)),
        ));

        foreach ($this->notify as $topic => $row) {
            $settings->set("notify.{$topic}.enabled", $row['on'], auth()->user());
            $settings->set("notify.{$topic}.email", $row['email'], auth()->user());
        }

        $off = collect(MailDispatcher::ADMIN_TOPICS)->filter(fn ($t, $k) => ! $this->notify[$k]['on'])->count();
        $this->notice = $off === 0 ? 'Admin email settings saved.' : "Admin email settings saved. {$off} kind".($off === 1 ? ' is' : 's are').' switched off.';
    }

    public function render(SettingsRepository $settings): View
    {
        $this->authorizeAdmin();

        return view('livewire.admin-settings', [
            'topics' => MailDispatcher::ADMIN_TOPICS,
            'sender_address' => (string) config('mail.from.address'),
            'platform_name' => app(\App\Services\BrandService::class)->name(),
            'company_email' => app(MailDispatcher::class)->companyEmail(),
            'otp' => [
                'ttl' => OtpService::TTL_MINUTES,
                'attempts' => OtpService::MAX_ATTEMPTS,
                'cooldown' => OtpService::COOLDOWN_SECONDS,
                'hourly' => OtpService::HOURLY_CAP,
            ],
            'terms_version' => (string) config('drinkwell.terms_version'),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === Role::Admin, 403);
    }
}
