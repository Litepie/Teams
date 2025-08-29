<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
use Litepie\Logs\Facades\Logs;
use Litepie\Teams\Events\TeamArchived;
use Litepie\Teams\Models\Team;

/**
 * ArchiveTeamAction
 * 
 * Archives a team permanently, preserving data but making it inactive.
 */
class ArchiveTeamAction extends StandardAction
{
    /**
     * Validation rules for archiving a team.
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'string', 'exists:teams,id'],
            'archived_by' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:1000'],
            'preserve_data' => ['boolean'],
        ];
    }

    /**
     * Authorize the archival request.
     */
    protected function authorize(): void
    {
        $team = Team::find($this->data['team_id']);
        
        if (!$team) {
            throw new AuthorizationException('Team not found.');
        }

        // Check if user has permission to archive teams
        if (!$this->user->hasPermissionTo('archive_team', $team)) {
            throw new AuthorizationException('You do not have permission to archive teams.');
        }

        // Team must be active or suspended to be archived
        if (!in_array($team->status, ['active', 'suspended'])) {
            throw new AuthorizationException('Team cannot be archived from its current state.');
        }
    }

    /**
     * Handle the team archival.
     */
    protected function handle(): array
    {
        return DB::transaction(function () {
            $team = Team::find($this->data['team_id']);
            $preserveData = $this->data['preserve_data'] ?? true;
            
            // Update team status
            $team->update([
                'status' => 'archived',
                'archived_at' => now(),
                'archived_by' => $this->data['archived_by'],
                'settings' => array_merge($team->settings ?? [], [
                    'archive_reason' => $this->data['reason'],
                    'archive_date' => now()->toISOString(),
                    'preserve_data' => $preserveData,
                ])
            ]);

            // Archive team members if not preserving data
            if (!$preserveData) {
                $team->members()->update([
                    'status' => 'archived',
                    'archived_at' => now(),
                ]);
            }

            // Clear any cached team data
            Cache::tags(['team:' . $team->id])->flush();

            // Fire team archived event
            event(new TeamArchived($team, $this->user, $this->data['reason']));

            // Log the activity
            Logs::activity()
                ->on($team)
                ->by($this->user)
                ->withData([
                    'reason' => $this->data['reason'],
                    'preserve_data' => $preserveData,
                    'previous_status' => $team->getOriginal('status'),
                ])
                ->log('Team archived');

            return [
                'success' => true,
                'team' => $team->fresh(),
                'message' => 'Team has been successfully archived.',
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

            // Send notifications to team members about archival
            $this->executeSubAction('NotifyTeamMembersAction', [
                'team_id' => $team->id,
                'notification_type' => 'team_archived',
                'message' => 'Your team has been archived. Reason: ' . $team->settings['archive_reason'],
            ]);

            // Clean up team resources if not preserving data
            if (!($team->settings['preserve_data'] ?? true)) {
                $this->executeSubAction('CleanupTeamResourcesAction', [
                    'team_id' => $team->id,
                ]);
            }

            // Archive team files if configured
            $this->executeSubAction('ArchiveTeamFilesAction', [
                'team_id' => $team->id,
                'preserve_data' => $team->settings['preserve_data'] ?? true,
            ]);
        }
    }
}
