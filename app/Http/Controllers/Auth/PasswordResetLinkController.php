<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Enums\Role;
use App\Exceptions\MailSendFailed;
use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\FormGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Step one of the code reset (SPEC 5.2, 5.3): ask for the email, send a code. */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * The answer is the same whether or not the email belongs to an account.
     * Only a transport failure is shown, so the user knows to try again.
     */
    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        $email = OtpService::normalize($request->string('email')->toString());

        // A bot gets the normal answer and nothing is sent.
        if (! FormGuard::request($request, 'forgot-password')) {
            return $this->sent($email);
        }

        $user = User::query()->where('email', $email)->where('role', '!=', Role::Operator)->first();

        if ($user) {
            try {
                $otp->issue($email, OtpPurpose::ResetPassword);
            } catch (MailSendFailed $e) {
                return back()->withInput($request->only('email'))->withErrors(['email' => $e->getMessage()]);
            } catch (OtpException) {
                // Cooldown or hourly cap. Say nothing different: the earlier code is still valid.
            }
        }

        return $this->sent($email);
    }

    private function sent(string $email): RedirectResponse
    {
        return redirect()->route('password.reset')
            ->with('reset_email', $email)
            ->with('status', 'If an account exists for '.$email.', we sent a six digit code. It expires in 10 minutes.');
    }
}
