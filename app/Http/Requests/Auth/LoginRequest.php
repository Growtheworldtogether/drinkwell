<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use App\Support\AdminLockout;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // A locked admin email refuses even the right password, with the same words as any failed sign-in.
        if (AdminLockout::isLocked($this->string('email')->toString())) {
            logger()->channel('security')->warning('Sign-in refused: admin locked', ['email' => $this->string('email')->toString(), 'ip' => $this->ip()]);

            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        // Email and password only, for every role including admin (SPEC 5.1).
        // Operators and accounts with a null password can never match.
        $credentials = $this->only('email', 'password') + [
            fn (Builder $query) => $query
                ->where('role', '!=', Role::Operator)
                ->whereNotNull('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            // Admin emails get five tries per 15 minutes, everyone else five per minute (SPEC 17.2).
            RateLimiter::hit($this->throttleKey(), AdminLockout::isAdminEmail($this->string('email')->toString()) ? (int) config('security.admin_lockout_minutes') * 60 : 60);
            logger()->channel('security')->info('Failed sign-in', ['email' => $this->string('email')->toString(), 'ip' => $this->ip()]);
            AdminLockout::fail($this->string('email')->toString());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // The password was right, so the suspension reason is shown only to the account owner.
        $user = Auth::user();

        if ($user->isSuspended()) {
            $message = $user->suspensionNotice();
            Auth::guard('web')->logout();

            throw ValidationException::withMessages(['email' => $message]);
        }

        RateLimiter::clear($this->throttleKey());
        AdminLockout::clear($this->string('email')->toString());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
