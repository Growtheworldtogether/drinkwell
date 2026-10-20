<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    /** Change the signed-in user's password. The current password is required. */
    public function update(Request $request, AccountService $accounts): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', PasswordRules::for($request->user()->role), 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'The new password must differ from the current one.',
        ]);

        $accounts->changePassword($request->user(), $validated['password']);

        return back()->with('status', 'password-updated');
    }
}
