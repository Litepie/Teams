<?php

declare(strict_types=1);

namespace Litepie\Teams;

use Litepie\Teams\Models\Team;
use Litepie\Teams\Models\TeamMember;
use Litepie\Teams\Models\TeamInvitation;

/**
 * Teams Main Class
 * 
 * Main service class for team operations.
 */
class Teams
{
    /**
     * Create a new team.
     */
    public function createTeam(array $data): Team
    {
        return Team::create($data);
    }

    /**
     * Get a team by ID.
     */
    public function getTeam(int $id): ?Team
    {
        return Team::find($id);
    }

    /**
     * Get teams for a user.
     */
    public function getUserTeams(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Team::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->where('status', 'active');
        })->get();
    }

    /**
     * Add a member to a team.
     */
    public function addMember(Team $team, int $userId, string $role = 'member'): TeamMember
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
     * Remove a member from a team.
     */
    public function removeMember(Team $team, int $userId): bool
    {
        return TeamMember::where('team_id', $team->id)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Invite a user to a team.
     */
    public function inviteUser(Team $team, string $email, string $role = 'member', $invitedBy = null): TeamInvitation
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
     * Check if a user is a member of a team.
     */
    public function isMember(Team $team, int $userId): bool
    {
        return TeamMember::where('team_id', $team->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get team statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_teams' => Team::count(),
            'total_members' => TeamMember::count(),
            'active_teams' => Team::where('status', 'active')->count(),
            'pending_invitations' => TeamInvitation::where('status', 'pending')->count(),
        ];
    }

    /**
     * Get team configuration.
     */
    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return config('teams');
        }

        return config("teams.{$key}", $default);
    }
}
