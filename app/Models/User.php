<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role', 'status', 'suspended_reason', 'phone',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /** Mirrors the column defaults so a freshly created model already has a role and status. */
    protected $attributes = [
        'role' => 'applicant',
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
        ];
    }

    /** Operators and accounts without a password can never sign in (SPEC 5.1). */
    public function canAuthenticate(): bool
    {
        return $this->role->canSignIn() && filled($this->password);
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    /** The message shown when a suspended user is refused, with the stored reason. */
    public function suspensionNotice(): string
    {
        $reason = trim((string) $this->suspended_reason);

        return $reason === ''
            ? 'This account is suspended. Contact support.'
            : 'This account is suspended. Reason: '.$reason;
    }

    /** Role home as a relative URL, or null for an account that cannot sign in. */
    public function homeUrl(): ?string
    {
        $route = $this->role->homeRoute();

        return $route ? route($route, absolute: false) : null;
    }

    /** @return HasOne<PartnerProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(PartnerProfile::class);
    }

    /** @return HasOne<PairingRequest, $this> */
    public function pairingRequest(): HasOne
    {
        return $this->hasOne(PairingRequest::class);
    }

    /** @return HasMany<MachineOwnership, $this> */
    public function ownerships(): HasMany
    {
        return $this->hasMany(MachineOwnership::class);
    }

    /** @return HasMany<PayoutAccount, $this> */
    public function payoutAccounts(): HasMany
    {
        return $this->hasMany(PayoutAccount::class);
    }

    /** @return HasMany<Withdrawal, $this> */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /** @return HasMany<DailyDistribution, $this> */
    public function distributions(): HasMany
    {
        return $this->hasMany(DailyDistribution::class);
    }

    // Scopes (SPEC 9.3)

    /** @param  Builder<$this>  $query */
    public function scopePartners(Builder $query): void
    {
        $query->where('role', Role::Partner);
    }

    /** @param  Builder<$this>  $query */
    public function scopeApplicants(Builder $query): void
    {
        $query->where('role', Role::Applicant);
    }

    /** @param  Builder<$this>  $query */
    public function scopeSuspended(Builder $query): void
    {
        $query->where('status', UserStatus::Suspended);
    }
}
