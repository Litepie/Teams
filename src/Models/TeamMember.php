<?php

declare(strict_types=1);

namespace Litepie\Teams\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Litepie\Tenancy\Traits\BelongsToTenant;

/**
 * TeamMember Pivot Model
 * 
 * Represents the relationship between a team and its members with additional
 * metadata such as role, permissions, and activity tracking.
 * 
 * @property string $id
 * @property string $team_id
 * @property string $user_id
 * @property string $user_type
 * @property string $role
 * @property array $permissions
 * @property string $status
 * @property \Carbon\Carbon|null $joined_at
 * @property \Carbon\Carbon|null $last_activity_at
 * @property string|null $tenant_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Team $team
 * @property-read \Illuminate\Database\Eloquent\Model $user
 */
class TeamMember extends Pivot
{
    use HasFactory;
    use HasUuids;
    use BelongsToTenant;

    /**
     * The table associated with the model.
     */
    protected $table = 'team_members';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'user_type',
        'role',
        'permissions',
        'status',
        'joined_at',
        'last_activity_at',
        'tenant_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'is_active',
        'is_admin',
        'days_since_joined',
    ];

    /**
     * Get the team that owns the member.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user (polymorphic relationship).
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine if the member is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine if the member is an admin.
     */
    public function getIsAdminAttribute(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    /**
     * Get days since the member joined.
     */
    public function getDaysSinceJoinedAttribute(): int
    {
        return $this->joined_at?->diffInDays(now()) ?? 0;
    }

    /**
     * Check if the member has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        
        return in_array($permission, $permissions) || 
               in_array('*', $permissions);
    }

    /**
     * Check if the member has any of the specified permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $memberPermissions = $this->permissions ?? [];
        
        if (in_array('*', $memberPermissions)) {
            return true;
        }

        return !empty(array_intersect($permissions, $memberPermissions));
    }

    /**
     * Check if the member has all of the specified permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $memberPermissions = $this->permissions ?? [];
        
        if (in_array('*', $memberPermissions)) {
            return true;
        }

        return empty(array_diff($permissions, $memberPermissions));
    }

    /**
     * Add permission to the member.
     */
    public function addPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            return $this->update(['permissions' => $permissions]);
        }

        return true;
    }

    /**
     * Remove permission from the member.
     */
    public function removePermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_diff($permissions, [$permission]);
        
        return $this->update(['permissions' => array_values($permissions)]);
    }

    /**
     * Update last activity timestamp.
     */
    public function updateLastActivity(): bool
    {
        return $this->update(['last_activity_at' => now()]);
    }

    /**
     * Activate the member.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the member.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Suspend the member.
     */
    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }

    /**
     * Scope query to active members.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope query to members with specific role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope query to admin members.
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['owner', 'admin']);
    }

    /**
     * Scope query to members with specific permission.
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->where(function ($q) use ($permission) {
            $q->whereJsonContains('permissions', $permission)
              ->orWhereJsonContains('permissions', '*');
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TeamMember $member) {
            if (empty($member->status)) {
                $member->status = 'active';
            }
            
            if (empty($member->joined_at)) {
                $member->joined_at = now();
            }

            if (empty($member->user_type) && $member->user_id) {
                $member->user_type = config('auth.providers.users.model');
            }
        });

        static::created(function (TeamMember $member) {
            $member->team?->increment('members_count');
            event(new \Litepie\Teams\Events\MemberJoinedTeam($member->team, $member->user));
        });

        static::deleted(function (TeamMember $member) {
            $member->team?->decrement('members_count');
            event(new \Litepie\Teams\Events\MemberLeftTeam($member->team, $member->user));
        });

        static::updated(function (TeamMember $member) {
            if ($member->wasChanged('role')) {
                event(new \Litepie\Teams\Events\MemberRoleChanged(
                    $member->team, 
                    $member->user, 
                    $member->getOriginal('role'), 
                    $member->role
                ));
            }
        });
    }
}
