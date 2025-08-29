<?php

declare(strict_types=1);

namespace Litepie\Teams\Contracts;

use Litepie\Teams\Models\TeamMember;
use Litepie\Teams\Models\Team;

/**
 * TeamMemberRepository Contract
 * 
 * Interface for team member repository operations.
 */
interface TeamMemberRepository
{
    /**
     * Find a team member by ID.
     */
    public function find(int $id): ?TeamMember;

    /**
     * Find a team member by ID or fail.
     */
    public function findOrFail(int $id): TeamMember;

    /**
     * Create a new team member.
     */
    public function create(array $data): TeamMember;

    /**
     * Update a team member.
     */
    public function update(TeamMember $member, array $data): TeamMember;

    /**
     * Delete a team member.
     */
    public function delete(TeamMember $member): bool;

    /**
     * Get members for a team.
     */
    public function getMembersForTeam(int $teamId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get teams for a user.
     */
    public function getTeamsForUser(int $userId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Add a user to a team.
     */
    public function addUserToTeam(Team $team, int $userId, string $role = 'member'): TeamMember;

    /**
     * Remove a user from a team.
     */
    public function removeUserFromTeam(Team $team, int $userId): bool;

    /**
     * Check if user is member of team.
     */
    public function isUserMemberOfTeam(int $userId, int $teamId): bool;

    /**
     * Get member by team and user.
     */
    public function getMemberByTeamAndUser(int $teamId, int $userId): ?TeamMember;

    /**
     * Get members by status.
     */
    public function getMembersByStatus(string $status): \Illuminate\Database\Eloquent\Collection;
}
