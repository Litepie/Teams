<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
use Litepie\Logs\Facades\Logs;
use Litepie\Teams\Events\TeamSuspended;
use Litepie\Teams\Models\Team;

/**
 * SuspendTeamAction
 * 
 * Suspends a team temporarily, restricting access while preserving data.
 */
class SuspendTeamAction extends StandardAction
{
    /**
     * Validation rules for suspending a team.
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'string', 'exists:teams,id'],
            'suspended_by' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:1000'],
            'suspension_until' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Authorize the suspension request.
     */
    protected function authorize(): void
    {
        $team = Team::find($this->data['team_id']);
        
        if (!$team) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Team not found.');
        }

        // Check if user has permission to suspend teams
        if (!$this->user->hasPermissionTo('suspend_team', $team)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You do not have permission to suspend teams.');
        }

        // Team must be active to be suspended
        if ($team->status !== 'active') {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only active teams can be suspended.');
        }
    }

    /**
     * Handle the team suspension.
     */
    protected function handle(): array
    {
        return DB::transaction(function () {
            $team = Team::find($this->data['team_id']);
            
            // Update team status
            $team->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspended_by' => $this->data['suspended_by'],
                'settings' => array_merge($team->settings ?? [], [
                    'suspension_reason' => $this->data['reason'],
                    'suspension_until' => $this->data['suspension_until'] ?? null,
                    'suspension_date' => now()->toISOString(),
                ])
            ]);

            // Clear any cached team data
            Cache::tags(['team:' . $team->id])->flush();

            // Fire team suspended event
            event(new TeamSuspended($team, $this->user, $this->data['reason']));

            // Log the activity
            Logs::activity()
                ->on($team)
                ->by($this->user)
                ->withData([
                    'reason' => $this->data['reason'],
                    'suspension_until' => $this->data['suspension_until'] ?? null,
                    'previous_status' => $team->getOriginal('status'),
                ])
                ->log('Team suspended');

            return [
                'success' => true,
                'team' => $team->fresh(),
                'message' => 'Team has been successfully suspended.',
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

            // Send notifications to team members about suspension
            $this->executeSubAction('NotifyTeamMembersAction', [
                'team_id' => $team->id,
                'notification_type' => 'team_suspended',
                'message' => 'Your team has been suspended. Reason: ' . $team->settings['suspension_reason'],
            ]);

            // Revoke active sessions for team members if needed
            $this->executeSubAction('RevokeTeamSessionsAction', [
                'team_id' => $team->id,
            ]);
        }
    }
}
