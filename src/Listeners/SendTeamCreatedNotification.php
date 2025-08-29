<?php

declare(strict_types=1);

namespace Litepie\Teams\Listeners;

use Litepie\Teams\Events\TeamCreated;
use Illuminate\Support\Facades\Notification;

/**
 * SendTeamCreatedNotification Listener
 * 
 * Sends notifications when a team is created.
 */
class SendTeamCreatedNotification
{
    /**
     * Handle the event.
     */
    public function handle(TeamCreated $event): void
    {
        $team = $event->team;
        $creator = $event->creator;

        // Send notification to team creator
        if ($creator && config('teams.notifications.team_created', true)) {
            // You would typically send a notification here
            // Notification::send($creator, new TeamCreatedNotification($team));
            
            \Log::info("Team '{$team->name}' created by user {$creator->id}");
        }

        // Send notification to administrators if configured
        if (config('teams.notifications.notify_admins', false)) {
            $adminUsers = \App\Models\User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                // Notification::send($admin, new NewTeamCreatedNotification($team, $creator));
                \Log::info("Notifying admin {$admin->id} about new team {$team->id}");
            }
        }
    }
}
