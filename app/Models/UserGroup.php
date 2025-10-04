<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    /**
     * The admin who created this group
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Users that belong to this group
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_memberships')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    /**
     * Get users who can be impersonated by users in this group
     */
    public function getImpersonatableUsersAttribute()
    {
        return $this->users()->where('is_admin', false)->where('is_active', true)->get();
    }

    /**
     * Scope for active groups only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
