<?php

declare(strict_types=1);

namespace Litepie\Teams\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Litepie\Actions\StandardAction;
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
     * Authorize the member removal request.
     */
    public function authorize(array $data): bool
    {
        $team = Team::find($data['team_id']);
        $member = TeamMember::where('team_id', $data['team_id'])
                            ->where('user_id', $data['user_id'])
                            ->first();
        
        if (!$team || !$member) {
            return false;
        }

        // Check if user has permission to remove team members
        if (!$team->userHasPermission($this->user, 'remove_team_member')) {
            return false;
        }

        // Cannot remove the owner unless transferring ownership
        if ($member->role === 'owner' && !($data['transfer_ownership'] ?? false)) {
            $this->addError('user', 'Cannot remove team owner without transferring ownership.');
            return false;
        }

        // If transferring ownership, validate new owner
        if ($data['transfer_ownership'] ?? false) {
            $newOwner = TeamMember::where('team_id', $data['team_id'])
                                  ->where('user_id', $data['new_owner_id'])
                                  ->where('status', 'active')
                                  ->first();
            
            if (!$newOwner) {
                $this->addError('new_owner_id', 'New owner must be an active team member.');
                return false;
            }
        }

        return true;
    }

    /**
     * Execute the member removal.
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $team = Team::find($data['team_id']);
            $member = TeamMember::where('team_id', $data['team_id'])
                                ->where('user_id', $data['user_id'])
                                ->first();

            // Transfer ownership if needed
            if ($data['transfer_ownership'] ?? false) {
                $newOwner = TeamMember::where('team_id', $data['team_id'])
                                      ->where('user_id', $data['new_owner_id'])
                                      ->first();
                
                $newOwner->update(['role' => 'owner']);
                
                // Update team owner
                $team->update(['user_id' => $data['new_owner_id']]);
            }

            // Remove the member
            $member->update([
                'status' => 'removed',
                'removed_at' => now(),
                'removed_by' => $this->user->id,
                'removal_reason' => $data['reason'] ?? null,
            ]);

            // Clear cached team data
            Cache::tags(['team:' . $team->id, 'user:' . $data['user_id']])->flush();

            // Fire member removed event
            event(new TeamMemberRemoved($team, $member, $this->user));

            // Log the activity
            activity()
                ->performedOn($team)
                ->causedBy($this->user)
                ->withProperties([
                    'removed_user_id' => $data['user_id'],
                    'reason' => $data['reason'] ?? null,
                    'transfer_ownership' => $data['transfer_ownership'] ?? false,
                    'new_owner_id' => $data['new_owner_id'] ?? null,
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
    public function after(array $result): void
    {
        if ($result['success']) {
            $team = $result['team'];
            $member = $result['member'];

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
