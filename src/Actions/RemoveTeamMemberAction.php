<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
use Litepie\Logs\Facades\Logs;
use Litepie\Teams\Events\TeamMemberRemoved;
use Litepie\Teams\Models\Team;
use Litepie\Teams\Models\TeamMember;

/**
 * RemoveTeamMemberAction
 * 
 * Removes a member from a team with proper cleanup.
 */
class RemoveTeamMemberAction extends StandardAction
{
    /**
     * Validation rules for removing a team member.
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'string', 'exists:teams,id'],
            'user_id' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'transfer_ownership' => ['boolean'],
            'new_owner_id' => ['nullable', 'string', 'required_if:transfer_ownership,true'],
        ];
    }

    /**
     * Authorize the removal request.
     */
    protected function authorize(): void
    {
        $teamMember = TeamMember::find($this->data['team_member_id']);
        
        if (!$teamMember) {
            throw new AuthorizationException('Team member not found.');
        }

        $team = $teamMember->team;

        // Check if user has permission to remove team members
        if (!$team->userHasPermission($this->user, 'remove_team_member')) {
            throw new AuthorizationException('You do not have permission to remove team members.');
        }

        // Cannot remove yourself unless you're the owner and there's another owner
        if ($teamMember->user_id === $this->user->id) {
            if ($teamMember->role === 'owner') {
                $otherOwners = $team->members()
                    ->where('role', 'owner')
                    ->where('user_id', '!=', $this->user->id)
                    ->count();
                
                if ($otherOwners === 0) {
                    throw new AuthorizationException('Cannot remove yourself as the last owner. Transfer ownership first.');
                }
            }
        }
        
        // Cannot remove the last owner
        if ($teamMember->role === 'owner') {
            $ownerCount = $team->members()->where('role', 'owner')->count();
            if ($ownerCount <= 1) {
                throw new AuthorizationException('Cannot remove the last owner. Transfer ownership to another member first.');
            }
        }
    }    /**
     * Handle the member removal.
     */
    protected function handle(): array
    {
        return DB::transaction(function () {
            $team = Team::find($this->data['team_id']);
            $member = TeamMember::where('team_id', $this->data['team_id'])
                                ->where('user_id', $this->data['user_id'])
                                ->first();

            // Transfer ownership if needed
            if ($this->data['transfer_ownership'] ?? false) {
                $newOwner = TeamMember::where('team_id', $this->data['team_id'])
                                      ->where('user_id', $this->data['new_owner_id'])
                                      ->first();
                
                $newOwner->update(['role' => 'owner']);
                
                // Update team owner
                $team->update(['user_id' => $this->data['new_owner_id']]);
            }

            // Remove the member
            $member->update([
                'status' => 'removed',
                'removed_at' => now(),
                'removed_by' => $this->user->id,
                'removal_reason' => $this->data['reason'] ?? null,
            ]);

            // Clear cached team data
            Cache::tags(['team:' . $team->id, 'user:' . $this->data['user_id']])->flush();

            // Fire member removed event
            event(new TeamMemberRemoved($team, $member, $this->user));

            // Log the activity
            Logs::activity()
                ->on($team)
                ->by($this->user)
                ->withData([
                    'removed_user_id' => $this->data['user_id'],
                    'reason' => $this->data['reason'] ?? null,
                    'transfer_ownership' => $this->data['transfer_ownership'] ?? false,
                    'new_owner_id' => $this->data['new_owner_id'] ?? null,
                ])
                ->log('Team member removed');

            return [
                'success' => true,
                'team' => $team->fresh(),
                'member' => $member->fresh(),
                'message' => 'Team member has been successfully removed.',
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
            $member = $resultData['member'];

            // Send notification to removed member
            $this->executeSubAction('NotifyUserAction', [
                'user_id' => $member->user_id,
                'notification_type' => 'removed_from_team',
                'data' => [
                    'team_name' => $team->name,
                    'reason' => $member->removal_reason,
                ],
            ]);

            // Send notification to team members about removal
            $this->executeSubAction('NotifyTeamMembersAction', [
                'team_id' => $team->id,
                'notification_type' => 'member_removed',
                'exclude_user_ids' => [$member->user_id],
                'data' => [
                    'removed_member' => $member->user->name ?? 'Unknown',
                ],
            ]);

            // Clean up member-specific resources
            $this->executeSubAction('CleanupMemberResourcesAction', [
                'team_id' => $team->id,
                'user_id' => $member->user_id,
            ]);
        }
    }
}
