<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /** The code entry page. Verified users go straight to their home. */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        return $user->email_verified_at !== null
            ? redirect()->intended($user->homeUrl())
            : view('auth.verify-email', ['email' => $user->email]);
    }
}
