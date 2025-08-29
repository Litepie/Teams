<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Auth\Access\AuthorizationException;
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
     * Authorize the team update request.
     */
    protected function authorize(): void
    {
        $team = Team::find($this->data['team_id']);
        
        if (!$team) {
            throw new AuthorizationException('Team not found.');
        }

        // Check if user has permission to update teams
        if (!$team->userHasPermission($this->user, 'update_team')) {
            throw new AuthorizationException('You do not have permission to update this team.');
        }

        // Check if the team is in a state that allows updates
        if (in_array($team->status, ['archived', 'deleted'])) {
            throw new AuthorizationException('Cannot update teams in archived or deleted state.');
        }
    }

    /**
     * Handle the team update.
     */
    protected function handle(): array
    {
        return DB::transaction(function () {
            $team = Team::find($this->data['team_id']);
            $originalData = $team->toArray();
            
            // Prepare update data
            $updateData = [];
            
            if (isset($this->data['name'])) {
                $updateData['name'] = $this->data['name'];
                $updateData['slug'] = str($this->data['name'])->slug();
            }
            
            if (isset($this->data['description'])) {
                $updateData['description'] = $this->data['description'];
            }
            
            if (isset($this->data['settings'])) {
                $updateData['settings'] = array_merge($team->settings ?? [], $this->data['settings']);
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
    protected function after(): void
    {
        if ($this->result->isSuccess()) {
            $resultData = $this->result->getData();
            $team = $resultData['team'];

            // Send notification to team members about updates
            $this->executeSubAction('NotifyTeamMembersAction', [
                'team_id' => $team->id,
                'notification_type' => 'team_updated',
                'data' => [
                    'updated_by' => $this->user->name ?? 'Unknown',
                    'changes' => $this->getHumanReadableChanges($resultData),
                ],
            ]);

            // Update search index if team name changed
            if (isset($resultData['changes']['name'])) {
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
