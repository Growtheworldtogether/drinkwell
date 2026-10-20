<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /** Accept the six digit code and mark the signed-in user's email as verified. */
    public function __invoke(Request $request, OtpService $otp, AccountService $accounts): RedirectResponse
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return redirect()->intended($user->homeUrl());
        }

        $request->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'Enter the six digit code from the email.',
            'code.digits' => 'The code is six digits. Check the email and try again.',
        ]);

        try {
            $otp->verify($user->email, OtpPurpose::VerifyEmail, $request->string('code')->toString());
        } catch (OtpException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $accounts->markEmailVerified($user);

        // A new account always lands on its own home (the application for an applicant). A page the browser
        // wanted before signing up, left over in the session, must not take the user somewhere else.
        $request->session()->forget('url.intended');

        return redirect($user->homeUrl())->with('status', 'Your email address is verified.');
    }
}
