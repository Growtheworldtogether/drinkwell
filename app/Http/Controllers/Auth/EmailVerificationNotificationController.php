<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\MailSendFailed;
use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /** Send a new verification code. The 60 second cooldown and hourly cap apply. */
    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return redirect()->intended($user->homeUrl());
        }

        try {
            $otp->issue($user->email, OtpPurpose::VerifyEmail);
        } catch (MailSendFailed|OtpException $e) {
            return redirect()->route('verification.notice')->withErrors(['mail' => $e->getMessage()]);
        }

        return redirect()->route('verification.notice')->with('status', 'We sent a new six digit code to '.$user->email.'.');
    }
}
