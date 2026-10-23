<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * SMTP details saved from Admin, Settings, Email. When turned on they replace the MAIL_ lines in .env, so the
 * owner can connect a mailbox without editing files. The password is stored encrypted with APP_KEY.
 */
class MailSettings
{
    public const KEY = 'mail_smtp';

    public function __construct(private readonly SettingsRepository $settings) {}

    /** @return array{enabled: bool, host: string, port: int, username: string, from_address: string, from_name: string, has_password: bool} */
    public function stored(): array
    {
        $s = $this->settings->array(self::KEY);

        return [
            'enabled' => (bool) ($s['enabled'] ?? false),
            'host' => (string) ($s['host'] ?? ''),
            'port' => (int) ($s['port'] ?? 465),
            'username' => (string) ($s['username'] ?? ''),
            'from_address' => (string) ($s['from_address'] ?? ''),
            'from_name' => (string) ($s['from_name'] ?? ''),
            'has_password' => ($s['password'] ?? '') !== '',
        ];
    }

    /** Puts the saved details into the mail config for this request. Does nothing when they are off or unreadable. */
    public function apply(): void
    {
        try {
            $s = $this->settings->array(self::KEY);
            if (! ($s['enabled'] ?? false) || ($s['host'] ?? '') === '') {
                return;
            }
            $password = ($s['password'] ?? '') !== '' ? Crypt::decryptString($s['password']) : null;
        } catch (Throwable) {
            return;
        }

        $port = (int) ($s['port'] ?? 465);
        $from = ($s['from_address'] ?? '') !== '' ? $s['from_address'] : ($s['username'] ?? '');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $s['host'],
            'mail.mailers.smtp.port' => $port,
            // Port 465 talks TLS from the first byte; 587 and 25 start plain and upgrade with STARTTLS.
            'mail.mailers.smtp.scheme' => $port === 465 ? 'smtps' : 'smtp',
            'mail.mailers.smtp.username' => ($s['username'] ?? '') !== '' ? $s['username'] : null,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.timeout' => 15,
            'mail.from.address' => $from,
            'mail.from.name' => ($s['from_name'] ?? '') !== '' ? $s['from_name'] : config('app.name'),
        ]);

        if (app()->resolved('mail.manager')) {
            app('mail.manager')->purge('smtp');
        }
    }

    /**
     * @param  array{enabled: bool, host: string, port: int, username: string, password: ?string, from_address: string, from_name: string}  $in
     *                                                                                                                                       An empty password keeps the saved one.
     */
    public function save(array $in, User $admin): void
    {
        $old = $this->settings->array(self::KEY);
        $password = ($in['password'] ?? '') !== '' ? Crypt::encryptString($in['password']) : ($old['password'] ?? '');

        $value = [
            'enabled' => $in['enabled'],
            'host' => trim($in['host']),
            'port' => $in['port'],
            'username' => trim($in['username']),
            'password' => $password,
            'from_address' => trim($in['from_address']),
            'from_name' => trim($in['from_name']),
        ];

        // Written directly, not through SettingsRepository::set, so the audit row never holds the password.
        Setting::query()->updateOrCreate(['key' => self::KEY], ['value' => $value, 'updated_by' => $admin->id]);
        $this->settings->flush();

        $mask = fn (array $v) => array_diff_key($v, ['password' => 1]) + ['password' => ($v['password'] ?? '') !== '' ? 'set' : 'empty'];
        AuditLogger::log($admin, 'setting_updated', null, ['key' => self::KEY] + $mask($old), ['key' => self::KEY] + $mask($value) + ['password_changed' => ($in['password'] ?? '') !== '']);

        $this->apply();
    }
}
