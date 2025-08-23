<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
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
    public function authorize(array $data): bool
    {
        $team = Team::find($data['team_id']);
        
        if (!$team) {
            return false;
        }

        // Check if user has permission to restore teams
        if (!$this->user->hasPermissionTo('restore_team', $team)) {
            return false;
        }

        // Team must be archived to be restored
        if ($team->status !== 'archived') {
            $this->addError('team', 'Only archived teams can be restored.');
            return false;
        }

        return true;
    }

    /**
     * Execute the team restoration.
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $team = Team::find($data['team_id']);
            
            // Update team status
            $team->update([
                'status' => 'active',
                'restored_at' => now(),
                'restored_by' => $data['restored_by'],
                'archived_at' => null,
                'archived_by' => null,
                'settings' => array_merge($team->settings ?? [], [
                    'restoration_notes' => $data['notes'] ?? null,
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
            activity()
                ->performedOn($team)
                ->causedBy($this->user)
                ->withProperties([
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
    public function after(array $result): void
    {
        if ($result['success']) {
            $team = $result['team'];

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
