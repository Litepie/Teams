<?php

declare(strict_types=1);

namespace Litepie\Teams\Repositories;

use Litepie\Teams\Contracts\TeamMemberRepository as TeamMemberRepositoryContract;
use Litepie\Teams\Models\TeamMember;
use Litepie\Teams\Models\Team;

/**
 * TeamMemberRepository Implementation
 * 
 * Concrete implementation for team member repository operations.
 */
class TeamMemberRepository implements TeamMemberRepositoryContract
{
    /**
     * Find a team member by ID.
     */
    public function find(int $id): ?TeamMember
    {
        return TeamMember::find($id);
    }

    /**
     * Find a team member by ID or fail.
     */
    public function findOrFail(int $id): TeamMember
    {
        return TeamMember::findOrFail($id);
    }

    /**
     * Create a new team member.
     */
    public function create(array $data): TeamMember
    {
        return TeamMember::create($data);
    }

    /**
     * Update a team member.
     */
    public function update(TeamMember $member, array $data): TeamMember
    {
        $member->update($data);
        return $member->fresh();
    }

    /**
     * Delete a team member.
     */
    public function delete(TeamMember $member): bool
    {
        return $member->delete();
    }

    /**
     * Get members for a team.
     */
    public function getMembersForTeam(int $teamId): \Illuminate\Database\Eloquent\Collection
    {
        return TeamMember::where('team_id', $teamId)->get();
    }

    /**
     * Get teams for a user.
     */
    public function getTeamsForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return TeamMember::where('user_id', $userId)
            ->with('team')
            ->get()
            ->pluck('team');
    }

    /**
     * Add a user to a team.
     */
    public function addUserToTeam(Team $team, int $userId, string $role = 'member'): TeamMember
    {
        return TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $userId,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove a user from a team.
     */
    public function removeUserFromTeam(Team $team, int $userId): bool
    {
        return TeamMember::where('team_id', $team->id)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Check if user is member of team.
     */
    public function isUserMemberOfTeam(int $userId, int $teamId): bool
    {
        return TeamMember::where('user_id', $userId)
            ->where('team_id', $teamId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get member by team and user.
     */
    public function getMemberByTeamAndUser(int $teamId, int $userId): ?TeamMember
    {
        return TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get members by status.
     */
    public function getMembersByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return TeamMember::where('status', $status)->get();
    }
}
