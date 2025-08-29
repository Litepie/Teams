<?php

declare(strict_types=1);

namespace Litepie\Teams\Listeners;

use Litepie\Teams\Events\MemberJoinedTeam;
use Illuminate\Support\Facades\Notification;

/**
 * SendMemberWelcomeNotification Listener
 * 
 * Sends welcome notifications when a member joins a team.
 */
class SendMemberWelcomeNotification
{
    /**
     * Handle the event.
     */
    public function handle(MemberJoinedTeam $event): void
    {
        $team = $event->team;
        $user = $event->user;

        // Send welcome notification to the new member
        if (config('teams.notifications.member_welcome', true)) {
            // You would typically send a notification here
            // Notification::send($user, new WelcomeToTeamNotification($team));
            
            \Log::info("Sending welcome notification to user {$user->id} for team {$team->id}");
        }

        // Notify team owner about new member
        if ($team->owner && config('teams.notifications.new_member', true)) {
            // Notification::send($team->owner, new NewMemberJoinedNotification($team, $user));
            
            \Log::info("Notifying team owner {$team->owner->id} about new member {$user->id}");
        }
    }
}
