<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Database\Eloquent\Model;
use Litepie\Actions\StandardAction;
use Litepie\Teams\Events\MemberJoinedTeam;
use Litepie\Teams\Models\Team;
use Litepie\Teams\Models\TeamMember;

/**
 * AddTeamMemberAction
 * 
 * Adds a user to a team with specified role and permissions.
 */
class AddTeamMemberAction extends StandardAction
{
    /**
     * The action name.
     */
    protected string $name = 'add_team_member';

    /**
     * The action description.
     */
    protected string $description = 'Add a member to a team';

    /**
     * Validation rules for the action data.
     */
    protected function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:owner,admin,manager,member,viewer',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'send_notification' => 'nullable|boolean',
        ];
    }

    /**
     * Authorization check for the action.
     */
    protected function authorize(): bool
    {
        if (!$this->model instanceof Team) {
            return false;
        }

        // Check if user can manage team members
        return $this->user && (
            $this->model->isOwnedBy($this->user) ||
            $this->model->userHasPermission($this->user, 'manage_team_members') ||
            $this->model->userHasAnyRole($this->user, ['owner', 'admin'])
        );
    }

    /**
     * Handle the action execution.
     */
    protected function handle(): TeamMember
    {
        $data = $this->getData();
        $team = $this->model;
        
        // Check if user is already a member
        if ($team->hasMember($this->getUserToAdd())) {
            throw new \InvalidArgumentException('User is already a member of this team');
        }

        // Get default permissions for role if not provided
        if (empty($data['permissions'])) {
            $data['permissions'] = $this->getDefaultPermissionsForRole($data['role']);
        }

        // Set default status
        $data['status'] = $data['status'] ?? 'active';

        // Add user type for polymorphic relationship
        $user = $this->getUserToAdd();
        $data['user_type'] = $user->getMorphClass();

        // Set tenant context if available
        if (config('teams.features.tenancy') && function_exists('tenancy')) {
            $tenant = tenancy()->current();
            if ($tenant) {
                $data['tenant_id'] = $tenant->id;
            }
        }

        // Create the team member record
        $teamMember = $team->teamMembers()->create($data);

        // Fire member joined event
        event(new MemberJoinedTeam($team, $user));

        return $teamMember;
    }

    /**
     * Get the user to add to the team.
     */
    protected function getUserToAdd(): Model
    {
        $userModel = config('auth.providers.users.model');
        $userId = $this->getData()['user_id'];
        
        return $userModel::findOrFail($userId);
    }

    /**
     * Get default permissions for a role.
     */
    protected function getDefaultPermissionsForRole(string $role): array
    {
        $roleConfig = config("teams.roles.default_roles.{$role}");
        
        if (!$roleConfig) {
            return config('teams.permissions.default_permissions', []);
        }

        return $roleConfig['permissions'] ?? [];
    }

    /**
     * Get notifications to send after the action.
     */
    protected function getNotifications(): array
    {
        if (!config('teams.notifications.enabled') || 
            !($this->getData()['send_notification'] ?? true)) {
            return [];
        }

        $user = $this->getUserToAdd();

        return [
            [
                'recipients' => [$user],
                'class' => \Litepie\Teams\Notifications\TeamMemberAddedNotification::class,
                'data' => [
                    'team' => $this->model,
                    'member' => $this->getResult()?->getData(),
                    'added_by' => $this->user,
                ],
            ],
        ];
    }

    /**
     * Get the action's description for the current status.
     */
    protected function getDescription(string $status): string
    {
        $teamName = $this->model?->name ?? 'Unknown';
        $userEmail = $this->getUserToAdd()?->email ?? 'Unknown';
        
        return match ($status) {
            'success' => "Successfully added {$userEmail} to team '{$teamName}'",
            'failure' => "Failed to add {$userEmail} to team '{$teamName}'",
            default => "Adding {$userEmail} to team '{$teamName}'",
        };
    }
}
