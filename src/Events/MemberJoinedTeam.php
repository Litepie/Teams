<?php

declare(strict_types=1);

namespace Litepie\Teams\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Litepie\Teams\Models\Team;

/**
 * MemberJoinedTeam Event
 * 
 * Fired when a member joins a team.
 */
class MemberJoinedTeam
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, $user)
    {
        $this->team = $team;
        $this->user = $user;
    }
}
