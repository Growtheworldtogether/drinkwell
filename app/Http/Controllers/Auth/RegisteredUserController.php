<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\MailSendFailed;
use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\AccountAlreadyExists;
use App\Services\AccountService;
use App\Services\MailDispatcher;
use App\Services\OtpService;
use App\Support\FormGuard;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Public registration always creates an applicant and emails a verification code.
     * If the email cannot be sent the account still exists and the verify page offers a resend.
     */
    public function store(Request $request, AccountService $accounts, OtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:190'],
            'password' => ['required', 'confirmed', PasswordRules::for()],
            'reference_code' => ['required', 'string', 'max:40', function (string $attribute, mixed $value, \Closure $fail) {
                if (! \App\Models\InviteCode::findUsable((string) $value)) {
                    $fail('That reference code is not valid. Check it with the person who gave it to you.');
                }
            }],
        ], ['reference_code.required' => 'Enter the reference code you were given.']);

        // A bot, or an address that already has an account, gets the same quiet answer (SPEC 17.2, no enumeration).
        if (! FormGuard::request($request, 'register')) {
            return $this->checkEmail($data['email']);
        }

        $existing = User::query()->where('email', $data['email'])->first();
        if ($existing) {
            app(MailDispatcher::class)->sendQuietly(new AccountAlreadyExists($existing->name), $existing);

            return $this->checkEmail($data['email']);
        }

        $invite = \App\Models\InviteCode::findUsable($data['reference_code']);
        if (! $invite || ! $invite->consume()) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['reference_code' => 'That reference code has just been used up. Ask for a new one.']);
        }

        $user = $accounts->registerApplicant($data['name'], $data['email'], $data['password']);
        $user->forceFill(['invite_code_id' => $invite->id])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        try {
            $otp->issue($user->email, OtpPurpose::VerifyEmail);
        } catch (MailSendFailed|OtpException $e) {
            return redirect()->route('verification.notice')->withErrors(['mail' => $e->getMessage()]);
        }

        return redirect()->route('verification.notice')->with('status', 'We sent a six digit code to '.$user->email.'.');
    }

    private function checkEmail(string $email): RedirectResponse
    {
        return redirect()->route('login')->with('status', 'Check your email at '.$email.'. If it is new to us, it has a six digit code to finish registering. If it already has an account, it has a message saying so.');
    }
}
