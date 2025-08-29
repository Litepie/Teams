<?php

declare(strict_types=1);

namespace Litepie\Teams\Repositories;

use Litepie\Teams\Contracts\TeamInvitationRepository as TeamInvitationRepositoryContract;
use Litepie\Teams\Models\TeamInvitation;
use Litepie\Teams\Models\Team;
use Litepie\Teams\Models\TeamMember;

/**
 * TeamInvitationRepository Implementation
 * 
 * Concrete implementation for team invitation repository operations.
 */
class TeamInvitationRepository implements TeamInvitationRepositoryContract
{
    /**
     * Find an invitation by ID.
     */
    public function find(int $id): ?TeamInvitation
    {
        return TeamInvitation::find($id);
    }

    /**
     * Find an invitation by ID or fail.
     */
    public function findOrFail(int $id): TeamInvitation
    {
        return TeamInvitation::findOrFail($id);
    }

    /**
     * Find an invitation by token.
     */
    public function findByToken(string $token): ?TeamInvitation
    {
        return TeamInvitation::where('token', $token)->first();
    }

    /**
     * Create a new invitation.
     */
    public function create(array $data): TeamInvitation
    {
        return TeamInvitation::create($data);
    }

    /**
     * Update an invitation.
     */
    public function update(TeamInvitation $invitation, array $data): TeamInvitation
    {
        $invitation->update($data);
        return $invitation->fresh();
    }

    /**
     * Delete an invitation.
     */
    public function delete(TeamInvitation $invitation): bool
    {
        return $invitation->delete();
    }

    /**
     * Get invitations for a team.
     */
    public function getInvitationsForTeam(int $teamId): \Illuminate\Database\Eloquent\Collection
    {
        return TeamInvitation::where('team_id', $teamId)->get();
    }

    /**
     * Get invitations by email.
     */
    public function getInvitationsByEmail(string $email): \Illuminate\Database\Eloquent\Collection
    {
        return TeamInvitation::where('email', $email)->get();
    }

    /**
     * Get invitations by status.
     */
    public function getInvitationsByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return TeamInvitation::where('status', $status)->get();
    }

    /**
     * Create team invitation.
     */
    public function createTeamInvitation(Team $team, string $email, string $role = 'member', $invitedBy = null): TeamInvitation
    {
        return TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $invitedBy,
            'token' => \Str::random(40),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Accept invitation.
     */
    public function acceptInvitation(TeamInvitation $invitation, int $userId): bool
    {
        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'user_id' => $userId,
        ]);

        // Create team member record
        TeamMember::create([
            'team_id' => $invitation->team_id,
            'user_id' => $userId,
            'role' => $invitation->role,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return true;
    }

    /**
     * Decline invitation.
     */
    public function declineInvitation(TeamInvitation $invitation): bool
    {
        return $invitation->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);
    }

    /**
     * Get expired invitations.
     */
    public function getExpiredInvitations(int $daysOld = 30): \Illuminate\Database\Eloquent\Collection
    {
        return TeamInvitation::where('status', 'pending')
            ->where('created_at', '<', now()->subDays($daysOld))
            ->get();
    }
}
