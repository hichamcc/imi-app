<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'api_key',
        'api_operator_id',
        'api_base_url',
        'is_admin',
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
        'api_key',
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
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * Check if user has valid API credentials
     */
    public function hasValidApiCredentials(): bool
    {
        return !empty($this->api_key)
            && !empty($this->api_operator_id)
            && !empty($this->api_base_url)
            && $this->is_active;
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Get user's API configuration array
     */
    public function getApiConfig(): array
    {
        return [
            'base_url' => $this->api_base_url,
            'key' => $this->api_key,
            'operator_id' => $this->api_operator_id,
        ];
    }

    /**
     * Scope to get only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * User's trucks relationship
     */
    public function trucks()
    {
        return $this->hasMany(\App\Models\Truck::class);
    }

    /**
     * Groups this user belongs to
     */
    public function userGroups()
    {
        return $this->belongsToMany(\App\Models\UserGroup::class, 'user_group_memberships')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    /**
     * Groups created by this user (admin)
     */
    public function createdGroups()
    {
        return $this->hasMany(\App\Models\UserGroup::class, 'created_by');
    }

    /**
     * Can this user impersonate other users
     */
    public function canImpersonate(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Check if user belongs to any groups
        return $this->userGroups()->exists();
    }

    /**
     * Can this user be impersonated by others
     */
    public function canBeImpersonated(): bool
    {
        return !$this->isAdmin() && $this->is_active;
    }

    /**
     * Get users this user can impersonate (from shared groups)
     */
    public function getImpersonatableUsers()
    {
        if ($this->isAdmin()) {
            // Admins can impersonate anyone
            return User::where('is_admin', false)->active()->get();
        }

        // Get all groups this user belongs to
        $userGroupIds = $this->userGroups()->pluck('user_groups.id');

        if ($userGroupIds->isEmpty()) {
            return collect(); // No groups, can't impersonate anyone
        }

        // Get all users from the same groups (excluding self and admins)
        return User::whereHas('userGroups', function ($query) use ($userGroupIds) {
            $query->whereIn('user_groups.id', $userGroupIds);
        })
        ->where('id', '!=', $this->id)
        ->where('is_admin', false)
        ->active()
        ->get();
    }

    /**
     * Check if this user can impersonate a specific user
     */
    public function canImpersonateUser(User $targetUser): bool
    {
        if ($this->isAdmin()) {
            return $targetUser->canBeImpersonated();
        }

        if (!$targetUser->canBeImpersonated()) {
            return false;
        }

        // Check if they share any groups
        $myGroupIds = $this->userGroups()->pluck('user_groups.id');
        $targetGroupIds = $targetUser->userGroups()->pluck('user_groups.id');

        return $myGroupIds->intersect($targetGroupIds)->isNotEmpty();
    }
}
