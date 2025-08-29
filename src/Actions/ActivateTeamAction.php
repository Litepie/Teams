<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
use Litepie\Logs\Facades\Logs;
use Litepie\Teams\Events\TeamActivated;
use Litepie\Teams\Models\Team;

/**
 * ActivateTeamAction
 * 
 * Activates a team, making it operational and accessible to its members.
 */
class ActivateTeamAction extends StandardAction
{
    /**
     * Validation rules for activating a team.
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'string', 'exists:teams,id'],
            'activated_by' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Authorize the activation request.
     */
    protected function authorize(): void
    {
        $team = Team::find($this->data['team_id']);
        
        if (!$team) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Team not found.');
        }

        // Check if user has permission to activate teams
        if (!$this->user->hasPermissionTo('activate_team', $team)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You do not have permission to activate teams.');
        }

        // Team must be in draft or suspended state to be activated
        if (!in_array($team->status, ['draft', 'suspended'])) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Team cannot be activated from its current state.');
        }
    }

    /**
     * Handle the team activation.
     */
    protected function handle(): array
    {
        return DB::transaction(function () {
            $team = Team::find($this->data['team_id']);
            
            // Update team status
            $team->update([
                'status' => 'active',
                'activated_at' => now(),
                'activated_by' => $this->data['activated_by'],
                'settings' => array_merge($team->settings ?? [], [
                    'activation_notes' => $this->data['notes'] ?? null,
                    'activation_date' => now()->toISOString(),
                ])
            ]);

            // Clear any cached team data
            Cache::tags(['team:' . $team->id])->flush();

            // Fire team activated event
            event(new TeamActivated($team, $this->user));

            // Log the activity
            Logs::activity()
                ->on($team)
                ->by($this->user)
                ->withData([
                    'notes' => $this->data['notes'] ?? null,
                    'previous_status' => $team->getOriginal('status'),
                ])
                ->log('Team activated');

            return [
                'success' => true,
                'team' => $team->fresh(),
                'message' => 'Team has been successfully activated.',
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

            // Send notifications to team members about activation
            $this->executeSubAction('NotifyTeamMembersAction', [
                'team_id' => $team->id,
                'notification_type' => 'team_activated',
                'message' => 'Your team has been activated and is now operational.',
            ]);

            // Initialize default team resources if needed
            $this->executeSubAction('InitializeTeamResourcesAction', [
                'team_id' => $team->id,
            ]);
        }
    }
}
