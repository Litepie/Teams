<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
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
    public function authorize(array $data): bool
    {
        $team = Team::find($data['team_id']);
        
        if (!$team) {
            return false;
        }

        // Check if user has permission to suspend teams
        if (!$this->user->hasPermissionTo('suspend_team', $team)) {
            return false;
        }

        // Team must be active to be suspended
        if ($team->status !== 'active') {
            $this->addError('team', 'Only active teams can be suspended.');
            return false;
        }

        return true;
    }

    /**
     * Execute the team suspension.
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $team = Team::find($data['team_id']);
            
            // Update team status
            $team->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspended_by' => $data['suspended_by'],
                'settings' => array_merge($team->settings ?? [], [
                    'suspension_reason' => $data['reason'],
                    'suspension_until' => $data['suspension_until'] ?? null,
                    'suspension_date' => now()->toISOString(),
                ])
            ]);

            // Clear any cached team data
            Cache::tags(['team:' . $team->id])->flush();

            // Fire team suspended event
            event(new TeamSuspended($team, $this->user, $data['reason']));

            // Log the activity
            activity()
                ->performedOn($team)
                ->causedBy($this->user)
                ->withProperties([
                    'reason' => $data['reason'],
                    'suspension_until' => $data['suspension_until'] ?? null,
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
    public function after(array $result): void
    {
        if ($result['success']) {
            $team = $result['team'];

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
