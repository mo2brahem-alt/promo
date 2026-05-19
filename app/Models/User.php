<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PROMO_MANAGER = 'promo_manager';
    public const ROLE_SELLER = 'seller';
    public const ROLE_REPORTS_MANAGER = 'reports_manager';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
        self::ROLE_PROMO_MANAGER,
        self::ROLE_SELLER,
        self::ROLE_REPORTS_MANAGER,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'is_active' => 'boolean',
        ];
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')->withTimestamps();
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromoCodeRedemption::class, 'seller_id');
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function canManagePromos(): bool
    {
        return $this->hasRole([self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_PROMO_MANAGER]);
    }

    public function canViewReports(): bool
    {
        return $this->hasRole([self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_PROMO_MANAGER, self::ROLE_REPORTS_MANAGER]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }
}
