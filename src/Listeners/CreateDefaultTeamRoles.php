<?php

declare(strict_types=1);

namespace Litepie\Teams\Listeners;

use Litepie\Teams\Events\TeamCreated;

/**
 * CreateDefaultTeamRoles Listener
 * 
 * Creates default roles when a team is created.
 */
class CreateDefaultTeamRoles
{
    /**
     * Handle the event.
     */
    public function handle(TeamCreated $event): void
    {
        $team = $event->team;

        // Create default roles for the team if roles system is enabled
        if (config('teams.features.roles', false)) {
            $defaultRoles = config('teams.default_roles', [
                'owner' => [
                    'name' => 'Owner',
                    'description' => 'Full access to team management',
                    'permissions' => ['*'],
                ],
                'admin' => [
                    'name' => 'Admin',
                    'description' => 'Administrative access',
                    'permissions' => ['manage_members', 'manage_settings'],
                ],
                'member' => [
                    'name' => 'Member',
                    'description' => 'Standard team member',
                    'permissions' => ['view_team'],
                ],
            ]);

            foreach ($defaultRoles as $roleKey => $roleData) {
                // This would typically create role records
                // Implementation depends on your role system
                \Log::info("Creating default role '{$roleKey}' for team {$team->id}");
            }
        }
    }
}
