<?php

declare(strict_types=1);

namespace Litepie\Teams\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Litepie\Teams\Models\Team;

/**
 * TeamActivated Event
 * 
 * Fired when a team is activated.
 */
class TeamActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public $activatedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, $activatedBy)
    {
        $this->team = $team;
        $this->activatedBy = $activatedBy;
    }
}

/**
 * TeamSuspended Event
 * 
 * Fired when a team is suspended.
 */
class TeamSuspended
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public $suspendedBy;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, $suspendedBy, string $reason)
    {
        $this->team = $team;
        $this->suspendedBy = $suspendedBy;
        $this->reason = $reason;
    }
}

/**
 * TeamArchived Event
 * 
 * Fired when a team is archived.
 */
class TeamArchived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public $archivedBy;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, $archivedBy, string $reason)
    {
        $this->team = $team;
        $this->archivedBy = $archivedBy;
        $this->reason = $reason;
    }
}

/**
 * TeamRestored Event
 * 
 * Fired when a team is restored from archived state.
 */
class TeamRestored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public $restoredBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, $restoredBy)
    {
        $this->team = $team;
        $this->restoredBy = $restoredBy;
    }
}

/**
 * TeamUpdated Event
 * 
 * Fired when a team is updated.
 */
class TeamUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public array $originalData;
    public $updatedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, array $originalData, $updatedBy)
    {
        $this->team = $team;
        $this->originalData = $originalData;
        $this->updatedBy = $updatedBy;
    }
}

/**
 * TeamMemberRemoved Event
 * 
 * Fired when a member is removed from a team.
 */
class TeamMemberRemoved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public $member;
    public $removedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, $member, $removedBy)
    {
        $this->team = $team;
        $this->member = $member;
        $this->removedBy = $removedBy;
    }
}

/**
 * TeamInvitationSent Event
 * 
 * Fired when a team invitation is sent.
 */
class TeamInvitationSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invitation;
    public Team $team;
    public $invitedBy;

    /**
     * Create a new event instance.
     */
    public function __construct($invitation, Team $team, $invitedBy)
    {
        $this->invitation = $invitation;
        $this->team = $team;
        $this->invitedBy = $invitedBy;
    }
}
