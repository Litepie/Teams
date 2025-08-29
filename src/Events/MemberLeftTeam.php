<?php

declare(strict_types=1);

namespace Litepie\Teams\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Litepie\Teams\Models\Team;

/**
 * MemberLeftTeam Event
 * 
 * Fired when a member leaves a team.
 */
class MemberLeftTeam
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
