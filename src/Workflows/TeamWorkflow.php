<?php

declare(strict_types=1);

namespace Litepie\Teams\Workflows;

use Litepie\Flow\States\State;
use Litepie\Flow\Transitions\Transition;
use Litepie\Flow\Workflows\Workflow;
use Litepie\Teams\Actions\ActivateTeamAction;
use Litepie\Teams\Actions\ArchiveTeamAction;
use Litepie\Teams\Actions\RestoreTeamAction;
use Litepie\Teams\Actions\SuspendTeamAction;

/**
 * TeamWorkflow
 * 
 * Defines the workflow for team lifecycle management with states
 * and transitions that can be applied to teams.
 */
class TeamWorkflow
{
    /**
     * Create the team workflow.
     */
    public static function create(): Workflow
    {
        $workflow = new Workflow('team_lifecycle', 'Team Lifecycle Management');

        // Define workflow states
        $draft = new State('draft', 'Draft', true);           // Initial state
        $active = new State('active', 'Active');
        $suspended = new State('suspended', 'Suspended');
        $archived = new State('archived', 'Archived', false, true); // Final state

        // Add states to workflow
        $workflow->addState($draft)
                 ->addState($active)
                 ->addState($suspended)
                 ->addState($archived);

        // Define transitions with actions
        $activateTransition = new Transition('draft', 'active', 'activate');
        $activateTransition->addAction(new ActivateTeamAction());

        $suspendTransition = new Transition('active', 'suspended', 'suspend');
        $suspendTransition->addAction(new SuspendTeamAction());

        $resumeTransition = new Transition('suspended', 'active', 'resume');
        $resumeTransition->addAction(new ActivateTeamAction());

        $archiveFromActiveTransition = new Transition('active', 'archived', 'archive');
        $archiveFromActiveTransition->addAction(new ArchiveTeamAction());

        $archiveFromSuspendedTransition = new Transition('suspended', 'archived', 'archive');
        $archiveFromSuspendedTransition->addAction(new ArchiveTeamAction());

        $restoreTransition = new Transition('archived', 'active', 'restore');
        $restoreTransition->addAction(new RestoreTeamAction());

        // Add transitions to workflow
        $workflow->addTransition($activateTransition)
                 ->addTransition($suspendTransition)
                 ->addTransition($resumeTransition)
                 ->addTransition($archiveFromActiveTransition)
                 ->addTransition($archiveFromSuspendedTransition)
                 ->addTransition($restoreTransition);

        return $workflow;
    }

    /**
     * Get available states for teams.
     */
    public static function getStates(): array
    {
        return [
            'draft' => [
                'name' => 'Draft',
                'description' => 'Team is being set up and not yet active',
                'color' => '#6b7280',
                'icon' => 'draft',
            ],
            'active' => [
                'name' => 'Active',
                'description' => 'Team is active and operational',
                'color' => '#10b981',
                'icon' => 'check-circle',
            ],
            'suspended' => [
                'name' => 'Suspended',
                'description' => 'Team is temporarily suspended',
                'color' => '#f59e0b',
                'icon' => 'pause-circle',
            ],
            'archived' => [
                'name' => 'Archived',
                'description' => 'Team is archived and no longer active',
                'color' => '#6b7280',
                'icon' => 'archive',
            ],
        ];
    }

    /**
     * Get available transitions for teams.
     */
    public static function getTransitions(): array
    {
        return [
            'activate' => [
                'name' => 'Activate',
                'description' => 'Activate the team',
                'from_states' => ['draft'],
                'to_state' => 'active',
                'permissions' => ['manage_team', 'activate_team'],
            ],
            'suspend' => [
                'name' => 'Suspend',
                'description' => 'Suspend the team temporarily',
                'from_states' => ['active'],
                'to_state' => 'suspended',
                'permissions' => ['manage_team', 'suspend_team'],
            ],
            'resume' => [
                'name' => 'Resume',
                'description' => 'Resume a suspended team',
                'from_states' => ['suspended'],
                'to_state' => 'active',
                'permissions' => ['manage_team', 'activate_team'],
            ],
            'archive' => [
                'name' => 'Archive',
                'description' => 'Archive the team permanently',
                'from_states' => ['active', 'suspended'],
                'to_state' => 'archived',
                'permissions' => ['manage_team', 'archive_team'],
            ],
            'restore' => [
                'name' => 'Restore',
                'description' => 'Restore an archived team',
                'from_states' => ['archived'],
                'to_state' => 'active',
                'permissions' => ['manage_team', 'restore_team'],
            ],
        ];
    }

    /**
     * Check if a transition is allowed for a user on a team.
     */
    public static function canTransition(string $transition, $team, $user): bool
    {
        $transitions = static::getTransitions();
        
        if (!isset($transitions[$transition])) {
            return false;
        }

        $transitionConfig = $transitions[$transition];
        
        // Check if user has required permissions
        foreach ($transitionConfig['permissions'] as $permission) {
            if (!$team->userHasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get allowed transitions for a team in its current state.
     */
    public static function getAllowedTransitions($team, $user): array
    {
        $currentState = $team->status ?? 'draft';
        $transitions = static::getTransitions();
        $allowed = [];

        foreach ($transitions as $transitionKey => $transition) {
            if (in_array($currentState, $transition['from_states']) &&
                static::canTransition($transitionKey, $team, $user)) {
                $allowed[] = $transitionKey;
            }
        }

        return $allowed;
    }
}
