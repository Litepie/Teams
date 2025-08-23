<?php

declare(strict_types=1);

namespace Litepie\Teams\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Litepie\Teams\Models\Team;
use Litepie\Teams\Models\TeamMember;

/**
 * TeamMemberAdded Event
 * 
 * Fired when a new member is added to a team.
 */
class TeamMemberAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public TeamMember $member;
    public $addedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, TeamMember $member, $addedBy)
    {
        $this->team = $team;
        $this->member = $member;
        $this->addedBy = $addedBy;
    }

    /**
     * Get the team.
     */
    public function getTeam(): Team
    {
        return $this->team;
    }

    /**
     * Get the team member.
     */
    public function getMember(): TeamMember
    {
        return $this->member;
    }

    /**
     * Get the user who added the member.
     */
    public function getAddedBy()
    {
        return $this->addedBy;
    }
}
