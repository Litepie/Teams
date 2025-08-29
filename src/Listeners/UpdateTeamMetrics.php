<?php

declare(strict_types=1);

namespace Litepie\Teams\Listeners;

use Litepie\Teams\Events\MemberJoinedTeam;
use Litepie\Teams\Events\MemberLeftTeam;
use Litepie\Teams\Events\TeamUpdated;

/**
 * UpdateTeamMetrics Listener
 * 
 * Updates team metrics when team-related events occur.
 */
class UpdateTeamMetrics
{
    /**
     * Handle member joined event.
     */
    public function handle($event): void
    {
        if ($event instanceof MemberJoinedTeam) {
            $this->handleMemberJoined($event);
        } elseif ($event instanceof MemberLeftTeam) {
            $this->handleMemberLeft($event);
        } elseif ($event instanceof TeamUpdated) {
            $this->handleTeamUpdated($event);
        }
    }

    /**
     * Handle when a member joins a team.
     */
    protected function handleMemberJoined(MemberJoinedTeam $event): void
    {
        $team = $event->team;
        
        // Update member count and last activity
        $team->increment('member_count');
        $team->touch('last_activity_at');
        
        \Log::info("Updated metrics for team {$team->id} - member joined");
    }

    /**
     * Handle when a member leaves a team.
     */
    protected function handleMemberLeft(MemberLeftTeam $event): void
    {
        $team = $event->team;
        
        // Update member count
        $team->decrement('member_count');
        $team->touch('last_activity_at');
        
        \Log::info("Updated metrics for team {$team->id} - member left");
    }

    /**
     * Handle when a team is updated.
     */
    protected function handleTeamUpdated(TeamUpdated $event): void
    {
        $team = $event->team;
        
        // Update last activity timestamp
        $team->touch('last_activity_at');
        
        \Log::info("Updated metrics for team {$team->id} - team updated");
    }
}
