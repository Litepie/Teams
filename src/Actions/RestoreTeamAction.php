<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
use Litepie\Logs\Facades\Logs;
use Litepie\Teams\Events\TeamRestored;
use Litepie\Teams\Models\Team;

/**
 * RestoreTeamAction
 * 
 * Restores an archived team, making it active again.
 */
class RestoreTeamAction extends StandardAction
{
    /**
     * Validation rules for restoring a team.
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'string', 'exists:teams,id'],
            'restored_by' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Authorize the restoration request.
     */
    protected function authorize(): void
    {
        $team = Team::find($this->data['team_id']);
        
        if (!$team) {
            throw new AuthorizationException('Team not found.');
        }

        // Check if user has permission to restore teams
        if (!$this->user->hasPermissionTo('restore_team', $team)) {
            throw new AuthorizationException('You do not have permission to restore teams.');
        }

        // Team must be archived or suspended to be restored
        if (!in_array($team->status, ['archived', 'suspended'])) {
            throw new AuthorizationException('Team cannot be restored from its current state.');
        }
    }

    /**
     * Handle the team restoration.
     */
    protected function handle(): array
    {
        return DB::transaction(function () {
            $team = Team::find($this->data['team_id']);
            
            // Update team status
            $team->update([
                'status' => 'active',
                'restored_at' => now(),
                'restored_by' => $this->data['restored_by'],
                'archived_at' => null,
                'archived_by' => null,
                'settings' => array_merge($team->settings ?? [], [
                    'restoration_notes' => $this->data['notes'] ?? null,
                    'restoration_date' => now()->toISOString(),
                ])
            ]);

            // Restore team members if they were archived
            $team->members()
                 ->where('status', 'archived')
                 ->update([
                     'status' => 'active',
                     'archived_at' => null,
                 ]);

            // Clear any cached team data
            Cache::tags(['team:' . $team->id])->flush();

            // Fire team restored event
            event(new TeamRestored($team, $this->user));

            // Log the activity
            Logs::activity()
                ->on($team)
                ->by($this->user)
                ->withData([
                    'notes' => $data['notes'] ?? null,
                    'previous_status' => $team->getOriginal('status'),
                ])
                ->log('Team restored');

            return [
                'success' => true,
                'team' => $team->fresh(),
                'message' => 'Team has been successfully restored.',
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

            // Send notifications to team members about restoration
            $this->executeSubAction('NotifyTeamMembersAction', [
                'team_id' => $team->id,
                'notification_type' => 'team_restored',
                'message' => 'Your team has been restored and is now active again.',
            ]);

            // Restore team resources if needed
            $this->executeSubAction('RestoreTeamResourcesAction', [
                'team_id' => $team->id,
            ]);

            // Restore team files if they were archived
            $this->executeSubAction('RestoreTeamFilesAction', [
                'team_id' => $team->id,
            ]);
        }
    }
}
