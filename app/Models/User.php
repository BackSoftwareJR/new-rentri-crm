<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'last_login_at',
        'sito_id',
        'deletion_requested_at',
        'deletion_reason',
        'deletion_scheduled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'password'                  => 'hashed',
            'two_factor_confirmed_at'   => 'datetime',
            'two_factor_recovery_codes' => 'array',
            'last_login_at'             => 'datetime',
            'active'                    => 'boolean',
            'deletion_requested_at'     => 'datetime',
            'deletion_scheduled_at'     => 'datetime',
        ];
    }

    public function sito(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sito::class);
    }

    public function pushSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null
            && filled($this->two_factor_secret);
    }

    public function twoFactorSecretPlain(): ?string
    {
        if (blank($this->two_factor_secret)) {
            return null;
        }

        try {
            return decrypt($this->two_factor_secret);
        } catch (\Throwable) {
            return null;
        }
    }
}
