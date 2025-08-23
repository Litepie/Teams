<?php

declare(strict_types=1);

namespace Litepie\Teams\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Litepie\Tenancy\Traits\BelongsToTenant;

/**
 * TeamInvitation Model
 * 
 * Represents an invitation to join a team.
 * 
 * @property string $id
 * @property string $team_id
 * @property string $email
 * @property string $token
 * @property string $role
 * @property array $permissions
 * @property string $status
 * @property string|null $message
 * @property string|null $invited_by_id
 * @property string|null $invited_by_type
 * @property string|null $accepted_by_id
 * @property string|null $accepted_by_type
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $accepted_at
 * @property \Carbon\Carbon|null $rejected_at
 * @property string|null $tenant_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Team $team
 * @property-read \Illuminate\Database\Eloquent\Model|null $invitedBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $acceptedBy
 */
class TeamInvitation extends Model
{
    use HasFactory;
    use HasUuids;
    use BelongsToTenant;

    /**
     * The table associated with the model.
     */
    protected $table = 'team_invitations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'team_id',
        'email',
        'token',
        'role',
        'permissions',
        'status',
        'message',
        'invited_by_id',
        'invited_by_type',
        'accepted_by_id',
        'accepted_by_type',
        'expires_at',
        'accepted_at',
        'rejected_at',
        'tenant_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'token',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'is_pending',
        'is_expired',
        'is_accepted',
        'is_rejected',
        'days_until_expiry',
    ];

    /**
     * Get the team that owns the invitation.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who accepted the invitation.
     */
    public function acceptedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine if the invitation is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    /**
     * Determine if the invitation is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Determine if the invitation is accepted.
     */
    public function getIsAcceptedAttribute(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Determine if the invitation is rejected.
     */
    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Accept the invitation.
     */
    public function accept(Model $user): bool
    {
        if (!$this->is_pending) {
            return false;
        }

        return $this->update([
            'status' => 'accepted',
            'accepted_by_id' => $user->getKey(),
            'accepted_by_type' => $user->getMorphClass(),
            'accepted_at' => now(),
        ]);
    }

    /**
     * Reject the invitation.
     */
    public function reject(): bool
    {
        if (!$this->is_pending) {
            return false;
        }

        return $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);
    }

    /**
     * Cancel the invitation.
     */
    public function cancel(): bool
    {
        if (!$this->is_pending) {
            return false;
        }

        return $this->update(['status' => 'cancelled']);
    }

    /**
     * Resend the invitation.
     */
    public function resend(): bool
    {
        if (!$this->is_pending) {
            return false;
        }

        $expiryDays = config('teams.invitations.expires_after_days', 7);

        return $this->update([
            'token' => $this->generateToken(),
            'expires_at' => now()->addDays($expiryDays),
        ]);
    }

    /**
     * Generate a unique token for the invitation.
     */
    protected function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Scope query to pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope query to expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Scope query to accepted invitations.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope query to rejected invitations.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope query by email.
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope query by token.
     */
    public function scopeForToken($query, string $token)
    {
        return $query->where('token', $token);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TeamInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = $invitation->generateToken();
            }

            if (empty($invitation->status)) {
                $invitation->status = 'pending';
            }

            if (empty($invitation->expires_at)) {
                $expiryDays = config('teams.invitations.expires_after_days', 7);
                $invitation->expires_at = now()->addDays($expiryDays);
            }

            if (empty($invitation->invited_by_type) && $invitation->invited_by_id) {
                $invitation->invited_by_type = config('auth.providers.users.model');
            }
        });

        static::created(function (TeamInvitation $invitation) {
            event(new \Litepie\Teams\Events\TeamInvitationSent($invitation));
        });

        static::updated(function (TeamInvitation $invitation) {
            if ($invitation->wasChanged('status')) {
                match ($invitation->status) {
                    'accepted' => event(new \Litepie\Teams\Events\TeamInvitationAccepted($invitation)),
                    'rejected' => event(new \Litepie\Teams\Events\TeamInvitationRejected($invitation)),
                    'cancelled' => event(new \Litepie\Teams\Events\TeamInvitationCancelled($invitation)),
                    default => null,
                };
            }
        });
    }
}
