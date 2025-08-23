<?php

declare(strict_types=1);

namespace Litepie\Teams\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Litepie\Teams\Models\Team;

/**
 * TeamCreated Event
 * 
 * Fired when a new team is created.
 */
class TeamCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;
    public $creator;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, $creator)
    {
        $this->team = $team;
        $this->creator = $creator;
    }

    /**
     * Get the team that was created.
     */
    public function getTeam(): Team
    {
        return $this->team;
    }

    /**
     * Get the user who created the team.
     */
    public function getCreator()
    {
        return $this->creator;
    }
}
