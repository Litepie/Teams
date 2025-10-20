<?php

declare(strict_types=1);

namespace Litepie\Teams\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Litepie\Actions\Traits\Actionable;
use Litepie\Database\Traits\Sluggable;
use Litepie\FileHub\Traits\HasFileAttachments;
use Litepie\Flow\Traits\HasWorkflow;
use Litepie\Tenancy\Traits\BelongsToTenant;

/**
 * Team Model
 * 
 * Represents a team within the application with full multi-tenant support,
 * role-based permissions, workflow management, and file attachments.
 * 
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $type
 * @property string $status
 * @property array $settings
 * @property array $metadata
 * @property string|null $tenant_id
 * @property string $owner_id
 * @property string $owner_type
 * @property int $members_count
 * @property int $files_count
 * @property float $storage_used
 * @property \Carbon\Carbon|null $last_activity_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Model $owner
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $members
 * @property-read \Illuminate\Database\Eloquent\Collection|TeamMember[] $teamMembers
 * @property-read \Illuminate\Database\Eloquent\Collection|TeamInvitation[] $invitations
 * @property-read \Illuminate\Database\Eloquent\Collection|TeamRole[] $roles
 */
class Team extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use Sluggable;
    use BelongsToTenant;
    use HasWorkflow;
    use Actionable;
    use HasFileAttachments;

    /**
     * The table associated with the model.
     */
    protected $table = 'teams';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'status',
        'settings',
        'metadata',
        'tenant_id',
        'owner_id',
        'owner_type',
        'last_activity_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'members_count' => 'integer',
        'files_count' => 'integer',
        'storage_used' => 'float',
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'metadata',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'is_active',
        'member_count',
        'storage_used_formatted',
    ];

    /**
     * Sluggable configuration.
     */
    protected array $slugs = [
        'slug' => 'name',
    ];

    /**
     * Slug configuration options.
     */
    protected array $slugConfig = [
        'separator' => '-',
        'language' => 'en',
        'max_length' => 50,
        'reserved_words' => ['admin', 'api', 'teams', 'team'],
        'unique' => true,
        'on_update' => false,
        'ascii_only' => true,
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the workflow name for this model.
     */
    public function getWorkflowName(): string
    {
        return 'team_lifecycle';
    }

    /**
     * Get the workflow state column.
     */
    protected function getWorkflowStateColumn(): string
    {
        return 'status';
    }

    /**
     * The owner of the team (polymorphic relationship).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the users that belong to the team.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            'team_members',
            'team_id',
            'user_id'
        )->using(TeamMember::class)
         ->withPivot([
             'role',
             'permissions',
             'joined_at',
             'last_activity_at',
             'status',
         ])
         ->withTimestamps();
    }

    /**
     * Get the team members (pivot records).
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get the team invitations.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the pending invitations.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class)
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now());
    }

    /**
     * Get the team roles.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(TeamRole::class);
    }

    /**
     * Determine if the team is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the member count attribute.
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members_count ?? $this->members()->count();
    }

    /**
     * Get formatted storage used.
     */
    public function getStorageUsedFormattedAttribute(): string
    {
        $bytes = $this->storage_used ?? 0;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Check if the given user is the owner of the team.
     */
    public function isOwnedBy(Model $user): bool
    {
        return $this->owner_id === $user->getKey() && 
               $this->owner_type === $user->getMorphClass();
    }

    /**
     * Check if the given user is a member of the team.
     */
    public function hasMember(Model $user): bool
    {
        return $this->members()->where('user_id', $user->getKey())->exists();
    }

    /**
     * Check if the given user has a specific role in the team.
     */
    public function userHasRole(Model $user, string $role): bool
    {
        return $this->members()
                    ->where('user_id', $user->getKey())
                    ->wherePivot('role', $role)
                    ->exists();
    }

    /**
     * Check if the given user has any of the specified roles in the team.
     */
    public function userHasAnyRole(Model $user, array $roles): bool
    {
        return $this->members()
                    ->where('user_id', $user->getKey())
                    ->whereIn('team_members.role', $roles)
                    ->exists();
    }

    /**
     * Check if the given user has a specific permission in the team.
     */
    public function userHasPermission(Model $user, string $permission): bool
    {
        $member = $this->members()->where('user_id', $user->getKey())->first();
        
        if (!$member) {
            return false;
        }

        $permissions = $member->pivot->permissions ?? [];
        
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * Get the user's role in the team.
     */
    public function getUserRole(Model $user): ?string
    {
        $member = $this->members()->where('user_id', $user->getKey())->first();
        
        return $member?->pivot?->role;
    }

    /**
     * Get the user's permissions in the team.
     */
    public function getUserPermissions(Model $user): array
    {
        $member = $this->members()->where('user_id', $user->getKey())->first();
        
        return $member?->pivot?->permissions ?? [];
    }

    /**
     * Add a member to the team.
     */
    public function addMember(Model $user, string $role = 'member', array $permissions = []): TeamMember
    {
        return $this->teamMembers()->create([
            'user_id' => $user->getKey(),
            'user_type' => $user->getMorphClass(),
            'role' => $role,
            'permissions' => $permissions,
            'joined_at' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * Remove a member from the team.
     */
    public function removeMember(Model $user): bool
    {
        return $this->teamMembers()
                    ->where('user_id', $user->getKey())
                    ->delete() > 0;
    }

    /**
     * Update a member's role.
     */
    public function updateMemberRole(Model $user, string $role): bool
    {
        return $this->teamMembers()
                    ->where('user_id', $user->getKey())
                    ->update(['role' => $role]) > 0;
    }

    /**
     * Update a member's permissions.
     */
    public function updateMemberPermissions(Model $user, array $permissions): bool
    {
        return $this->teamMembers()
                    ->where('user_id', $user->getKey())
                    ->update(['permissions' => $permissions]) > 0;
    }

    /**
     * Get team settings with defaults.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Update a team setting.
     */
    public function updateSetting(string $key, mixed $value): bool
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        
        return $this->update(['settings' => $settings]);
    }

    /**
     * Update team metadata.
     */
    public function updateMetadata(string $key, mixed $value): bool
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        
        return $this->update(['metadata' => $metadata]);
    }

    /**
     * Update last activity timestamp.
     */
    public function updateLastActivity(): bool
    {
        return $this->update(['last_activity_at' => now()]);
    }

    /**
     * Scope query to active teams.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope query to teams by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope query to teams owned by user.
     */
    public function scopeOwnedBy($query, Model $user)
    {
        return $query->where('owner_id', $user->getKey())
                    ->where('owner_type', $user->getMorphClass());
    }

    /**
     * Scope query to teams where user is a member.
     */
    public function scopeWhereUserIsMember($query, Model $user)
    {
        return $query->whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->getKey());
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->status)) {
                $team->status = 'draft';
            }
            
            $team->members_count = 0;
            $team->files_count = 0;
            $team->storage_used = 0;
        });

        static::created(function (Team $team) {
            event(new \Litepie\Teams\Events\TeamCreated($team));
        });

        static::updated(function (Team $team) {
            event(new \Litepie\Teams\Events\TeamUpdated($team));
        });

        static::deleted(function (Team $team) {
            event(new \Litepie\Teams\Events\TeamDeleted($team));
        });
    }
}
