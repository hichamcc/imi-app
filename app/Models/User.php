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
     * Can this user impersonate other users
     */
    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can this user be impersonated by others
     */
    public function canBeImpersonated(): bool
    {
        return !$this->isAdmin() && $this->is_active;
    }
}
