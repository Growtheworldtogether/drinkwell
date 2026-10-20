<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Enums\Role;
use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountService;
use App\Services\OtpService;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Step two of the code reset: email, code and new password. */
class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'email' => old('email', session('reset_email', $request->query('email', ''))),
        ]);
    }

    public function store(Request $request, OtpService $otp, AccountService $accounts): RedirectResponse
    {
        $email = OtpService::normalize($request->string('email')->toString());
        $user = User::query()->where('email', $email)->where('role', '!=', Role::Operator)->first();

        $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'digits:6'],
            // Baseline rule only, the same for every email. The stricter admin rule runs after the code is verified.
            'password' => ['required', 'confirmed', PasswordRules::for()],
        ], [
            'code.required' => 'Enter the six digit code from the email.',
            'code.digits' => 'The code is six digits. Check the email and try again.',
        ]);

        try {
            $otp->verify($email, OtpPurpose::ResetPassword, $request->string('code')->toString());
        } catch (OtpException $e) {
            return back()->withInput($request->only('email'))->withErrors(['code' => $e->getMessage()]);
        }

        // A valid code means an account existed when it was issued. Refuse if it was deleted since.
        if (! $user) {
            return back()->withInput($request->only('email'))->withErrors(['code' => OtpException::invalid()->getMessage()]);
        }

        // The code proved control of this account, so its role can now shape the rule without leaking it.
        if ($user->role === Role::Admin) {
            $request->validate(['password' => [PasswordRules::for(Role::Admin)]]);
        }

        $accounts->changePassword($user, $request->string('password')->toString(), reset: true);

        return redirect()->route('login')->with('status', 'Password changed. Sign in with the new password.');
    }
}
