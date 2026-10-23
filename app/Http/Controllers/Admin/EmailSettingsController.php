<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MailSendFailed;
use App\Http\Controllers\Controller;
use App\Mail\MailConnectionTest;
use App\Services\MailDispatcher;
use App\Services\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin, Settings, Email: the SMTP mailbox that sends sign-in codes and every other email. */
class EmailSettingsController extends Controller
{
    public function __construct(private readonly MailSettings $mail) {}

    public function show(): View
    {
        return view('admin.settings.email', [
            'm' => $this->mail->stored(),
            'current' => in_array(config('mail.default'), ['log', 'array'], true) ? null : ['host' => (string) config('mail.mailers.smtp.host'), 'from' => (string) config('mail.from.address')],
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $on = $request->boolean('enabled');
        $data = $request->validate([
            'host' => [$on ? 'required' : 'nullable', 'string', 'max:190', 'regex:/^[A-Za-z0-9.-]+$/'],
            'port' => [$on ? 'required' : 'nullable', 'integer', 'in:25,465,587,2525'],
            'username' => ['nullable', 'string', 'max:190'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => [$on && ! $request->filled('username') ? 'required' : 'nullable', 'email', 'max:190'],
            'from_name' => ['nullable', 'string', 'max:120'],
        ], [
            'host.regex' => 'Enter only the server name, for example mail.yourdomain.com.',
            'port.in' => 'Use 465 or 587 (your host lists the right one under Email Accounts, Connect Devices).',
        ]);

        $this->mail->save([
            'enabled' => $on,
            'host' => (string) ($data['host'] ?? ''),
            'port' => (int) ($data['port'] ?? 465),
            'username' => (string) ($data['username'] ?? ''),
            'password' => $data['password'] ?? null,
            'from_address' => (string) ($data['from_address'] ?? ''),
            'from_name' => (string) ($data['from_name'] ?? ''),
        ], $request->user());

        return redirect()->route('admin.settings.email')->with('status', $on
            ? 'Email settings saved. Send a test email below to check they work.'
            : 'Saved. The site now uses the email settings from the .env file.');
    }

    public function test(Request $request, MailDispatcher $dispatcher): RedirectResponse
    {
        $data = $request->validate(['to' => ['required', 'email', 'max:190']]);

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return back()->withInput()->with('error', 'Email is not connected yet, so nothing can be sent. Fill in the mailbox details above and save first.');
        }

        try {
            $dispatcher->send(new MailConnectionTest('smtp', (string) config('mail.mailers.smtp.host')), $data['to']);
        } catch (MailSendFailed $e) {
            return back()->withInput()->with('error', 'The mail server refused: '.($e->getPrevious()?->getMessage() ?? $e->getMessage()));
        }

        return back()->withInput()->with('status', "Test email sent to {$data['to']}. Check the inbox, and the spam folder too.");
    }
}
