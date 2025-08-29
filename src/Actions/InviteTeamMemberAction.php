<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Litepie\Actions\StandardAction;
use Litepie\Logs\Facades\Logs;
use Litepie\Teams\Events\TeamInvitationSent;
use Litepie\Teams\Models\Team;
use Litepie\Teams\Models\TeamInvitation;

/**
 * InviteTeamMemberAction
 * 
 * Sends an invitation to join a team to a user via email.
 */
class InviteTeamMemberAction extends StandardAction
{
    /**
     * Validation rules for inviting a team member.
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'string', 'exists:teams,id'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:member,admin,manager'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Authorize the invitation request.
     */
    protected function authorize(): void
    {
        $team = Team::find($this->data['team_id']);
        
        if (!$team) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Team not found.');
        }

        // Check if user has permission to invite team members
        if (!$team->userHasPermission($this->user, 'invite_team_member')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You do not have permission to invite team members.');
        }

        // Check if team has reached member limit
        if ($team->hasReachedMemberLimit()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Team has reached its member limit.');
        }

        // Check if invitation already exists for this email
        $existingInvitation = TeamInvitation::where('team_id', $this->data['team_id'])
                                           ->where('email', $this->data['email'])
                                           ->where('status', 'pending')
                                           ->first();

        if ($existingInvitation) {
            throw new \Illuminate\Auth\Access\AuthorizationException('An invitation for this email already exists.');
        }
    }

    /**
     * Handle the team invitation.
     */
    protected function handle(): array
    {
        return DB::transaction(function () {
            $team = Team::find($this->data['team_id']);
            
            // Create the invitation
            $invitation = TeamInvitation::create([
                'id' => Str::uuid(),
                'team_id' => $this->data['team_id'],
                'email' => $this->data['email'],
                'role' => $this->data['role'],
                'permissions' => $this->data['permissions'] ?? [],
                'token' => Str::random(64),
                'invited_by' => $this->user->id,
                'expires_at' => $this->data['expires_at'] ?? now()->addDays(7),
                'message' => $this->data['message'] ?? null,
                'status' => 'pending',
            ]);

            // Clear cached team data
            Cache::tags(['team:' . $team->id])->flush();

            // Fire invitation sent event
            event(new TeamInvitationSent($invitation, $team, $this->user));

            // Log the activity
            Logs::activity()
                ->on($team)
                ->by($this->user)
                ->withData([
                    'invited_email' => $this->data['email'],
                    'role' => $this->data['role'],
                    'permissions' => $this->data['permissions'] ?? [],
                ])
                ->log('Team invitation sent');

            return [
                'success' => true,
                'invitation' => $invitation->fresh(),
                'team' => $team,
                'message' => 'Team invitation has been successfully sent.',
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
            $invitation = $resultData['invitation'];
            $team = $resultData['team'];

            // Send invitation email
            $this->executeSubAction('SendInvitationEmailAction', [
                'invitation_id' => $invitation->id,
            ]);

            // Send notification to team members about new invitation
            $this->executeSubAction('NotifyTeamMembersAction', [
                'team_id' => $team->id,
                'notification_type' => 'invitation_sent',
                'data' => [
                    'invited_email' => $invitation->email,
                    'role' => $invitation->role,
                ],
            ]);

            // Schedule reminder if invitation is not accepted
            $this->executeSubAction('ScheduleInvitationReminderAction', [
                'invitation_id' => $invitation->id,
                'remind_at' => now()->addDays(3),
            ]);
        }
    }
}
