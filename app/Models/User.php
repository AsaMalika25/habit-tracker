<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const PRIMARY_ADMIN_EMAIL = 'asab6956@gmail.com';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => self::ROLE_USER,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
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
            'profile_completed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return array<string, string> */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_USER => 'Pengguna',
        ];
    }

    /** @return array<string, array{label: string, description: string, roles: list<string>}> */
    public static function capabilityDefinitions(): array
    {
        return [
            'manage_profile' => [
                'label' => 'Manage own profile',
                'description' => 'Update personal identity, timezone, and password.',
                'roles' => [self::ROLE_ADMIN, self::ROLE_USER],
            ],
            'manage_personal_data' => [
                'label' => 'Manage personal sanctuary',
                'description' => 'Create and edit owned habits, journals, and memories.',
                'roles' => [self::ROLE_ADMIN, self::ROLE_USER],
            ],
            'view_analytics' => [
                'label' => 'View personal analytics',
                'description' => 'Review insights calculated from owned records.',
                'roles' => [self::ROLE_ADMIN, self::ROLE_USER],
            ],
            'manage_roles' => [
                'label' => 'Manage users & roles',
                'description' => 'View registered accounts and change their authority.',
                'roles' => [self::ROLE_ADMIN],
            ],
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return self::roleLabels()[$this->role] ?? ucfirst($this->role);
    }

    public function hasCapability(string $capability): bool
    {
        $definition = self::capabilityDefinitions()[$capability] ?? null;

        return $definition !== null && in_array($this->role, $definition['roles'], true);
    }

    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    public function habitLogs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function streak(): HasOne
    {
        return $this->hasOne(UserStreak::class);
    }
}
