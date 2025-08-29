<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
use Litepie\Logs\Facades\Logs;
use Litepie\Teams\Events\TeamUpdated;
use Litepie\Teams\Models\Team;

/**
 * UpdateTeamAction
 * 
 * Updates a team's information and settings.
 */
class UpdateTeamAction extends StandardAction
{
    /**
     * Validation rules for updating a team.
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'string', 'exists:teams,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'settings' => ['nullable', 'array'],
            'settings.timezone' => ['nullable', 'string', 'timezone'],
            'settings.language' => ['nullable', 'string', 'max:10'],
            'settings.visibility' => ['nullable', 'in:public,private,restricted'],
            'settings.allow_invitations' => ['nullable', 'boolean'],
            'settings.max_members' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Authorize the update request.
     */
    public function authorize(array $data): bool
    {
        $team = Team::find($data['team_id']);
        
        if (!$team) {
            return false;
        }

        // Check if user has permission to update the team
        if (!$team->userHasPermission($this->user, 'update_team')) {
            return false;
        }

        // Validate unique team name if provided
        if (isset($data['name'])) {
            $existingTeam = Team::where('name', $data['name'])
                               ->where('id', '!=', $data['team_id'])
                               ->tenantScope()
                               ->first();

            if ($existingTeam) {
                $this->addError('name', 'A team with this name already exists.');
                return false;
            }
        }

        return true;
    }

    /**
     * Execute the team update.
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $team = Team::find($data['team_id']);
            $originalData = $team->toArray();
            
            // Prepare update data
            $updateData = [];
            
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
                $updateData['slug'] = str($data['name'])->slug();
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            
            if (isset($data['settings'])) {
                $updateData['settings'] = array_merge($team->settings ?? [], $data['settings']);
            }
            
            // Update the team
            $team->update($updateData);

            // Clear cached team data
            Cache::tags(['team:' . $team->id])->flush();

            // Fire team updated event
            event(new TeamUpdated($team, $originalData, $this->user));

            // Log the activity
            Logs::activity()
                ->on($team)
                ->by($this->user)
                ->withData([
                    'changes' => $updateData,
                    'original' => $originalData,
                ])
                ->log('Team updated');

            return [
                'success' => true,
                'team' => $team->fresh(),
                'message' => 'Team has been successfully updated.',
            ];
        });
    }

    /**
     * Handle any post-execution tasks.
     */
    public function after(array $result): void
    {
        if ($result['success']) {
            $team = $result['team'];

            // Send notification to team members about updates
            $this->executeSubAction('NotifyTeamMembersAction', [
                'team_id' => $team->id,
                'notification_type' => 'team_updated',
                'data' => [
                    'updated_by' => $this->user->name ?? 'Unknown',
                    'changes' => $this->getHumanReadableChanges($result),
                ],
            ]);

            // Update search index if team name changed
            if (isset($result['changes']['name'])) {
                $this->executeSubAction('UpdateTeamSearchIndexAction', [
                    'team_id' => $team->id,
                ]);
            }
        }
    }

    /**
     * Get human-readable changes for notifications.
     */
    private function getHumanReadableChanges(array $result): array
    {
        $changes = [];
        $updateData = $result['changes'] ?? [];

        if (isset($updateData['name'])) {
            $changes[] = 'Team name updated';
        }

        if (isset($updateData['description'])) {
            $changes[] = 'Team description updated';
        }

        if (isset($updateData['settings'])) {
            $changes[] = 'Team settings updated';
        }

        return $changes;
    }
}
