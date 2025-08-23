<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Database\Eloquent\Model;
use Litepie\Actions\StandardAction;
use Litepie\Teams\Events\TeamCreated;
use Litepie\Teams\Models\Team;

/**
 * CreateTeamAction
 * 
 * Creates a new team with the given data and automatically sets up
 * default roles, permissions, and team structure.
 */
class CreateTeamAction extends StandardAction
{
    /**
     * The action name.
     */
    protected string $name = 'create_team';

    /**
     * The action description.
     */
    protected string $description = 'Create a new team';

    /**
     * Validation rules for the action data.
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:project,department,organization,community,custom',
            'settings' => 'nullable|array',
            'settings.visibility' => 'nullable|string|in:public,private,invite_only',
            'settings.features' => 'nullable|array',
            'settings.limits' => 'nullable|array',
            'owner_id' => 'sometimes|required',
            'owner_type' => 'sometimes|required|string',
        ];
    }

    /**
     * Handle the action execution.
     */
    protected function handle(): Team
    {
        $data = $this->getData();
        
        // Set owner information if not provided
        if (!isset($data['owner_id']) && $this->user) {
            $data['owner_id'] = $this->user->getKey();
            $data['owner_type'] = $this->user->getMorphClass();
        }

        // Set default settings
        $data['settings'] = array_merge([
            'visibility' => 'private',
            'features' => ['file_sharing', 'workflows'],
            'limits' => [
                'max_members' => config('teams.limits.max_members_per_team', 100),
                'max_files' => config('teams.limits.max_files_per_team', 1000),
                'max_storage_gb' => config('teams.limits.max_storage_per_team_gb', 10),
            ],
        ], $data['settings'] ?? []);

        // Set tenant context if available
        if (config('teams.features.tenancy') && function_exists('tenancy')) {
            $tenant = tenancy()->current();
            if ($tenant) {
                $data['tenant_id'] = $tenant->id;
            }
        }

        // Create the team
        $team = Team::create($data);

        // Add the owner as a team member with owner role
        if ($this->user) {
            $team->addMember($this->user, 'owner', ['*']);
        }

        // Fire created event
        event(new TeamCreated($team));

        return $team;
    }

    /**
     * Get the sub-actions to execute after the main action.
     */
    protected function getSubActions(string $timing): array
    {
        if ($timing === 'after') {
            return [
                [
                    'action' => SetupTeamDefaultsAction::class,
                    'data' => ['team_id' => $this->getResult()?->getData()?->id],
                    'continue_on_failure' => true,
                ],
            ];
        }

        return [];
    }

    /**
     * Get notifications to send after the action.
     */
    protected function getNotifications(): array
    {
        if (!config('teams.notifications.enabled')) {
            return [];
        }

        return [
            [
                'recipients' => [$this->user],
                'class' => \Litepie\Teams\Notifications\TeamCreatedNotification::class,
                'data' => ['team' => $this->getResult()?->getData()],
            ],
        ];
    }

    /**
     * Get the action's description for the current status.
     */
    protected function getDescription(string $status): string
    {
        $teamName = $this->getData()['name'] ?? 'Unknown';
        
        return match ($status) {
            'success' => "Successfully created team '{$teamName}'",
            'failure' => "Failed to create team '{$teamName}'",
            default => "Creating team '{$teamName}'",
        };
    }
}
