<?php

declare(strict_types=1);

namespace Litepie\Teams\Repositories;

use Litepie\Teams\Contracts\TeamRepository as TeamRepositoryContract;
use Litepie\Teams\Models\Team;

/**
 * TeamRepository Implementation
 * 
 * Concrete implementation for team repository operations.
 */
class TeamRepository implements TeamRepositoryContract
{
    /**
     * Find a team by ID.
     */
    public function find(int $id): ?Team
    {
        return Team::find($id);
    }

    /**
     * Find a team by ID or fail.
     */
    public function findOrFail(int $id): Team
    {
        return Team::findOrFail($id);
    }

    /**
     * Create a new team.
     */
    public function create(array $data): Team
    {
        return Team::create($data);
    }

    /**
     * Update a team.
     */
    public function update(Team $team, array $data): Team
    {
        $team->update($data);
        return $team->fresh();
    }

    /**
     * Delete a team.
     */
    public function delete(Team $team): bool
    {
        return $team->delete();
    }

    /**
     * Get teams for a user.
     */
    public function getTeamsForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Team::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->where('status', 'active');
        })->get();
    }

    /**
     * Get teams by status.
     */
    public function getByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return Team::where('status', $status)->get();
    }

    /**
     * Search teams by name.
     */
    public function searchByName(string $name): \Illuminate\Database\Eloquent\Collection
    {
        return Team::where('name', 'like', "%{$name}%")->get();
    }

    /**
     * Get paginated teams.
     */
    public function paginate(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Team::paginate($perPage);
    }

    /**
     * Get team statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_teams' => Team::count(),
            'active_teams' => Team::where('status', 'active')->count(),
            'archived_teams' => Team::where('status', 'archived')->count(),
            'suspended_teams' => Team::where('status', 'suspended')->count(),
            'teams_created_today' => Team::whereDate('created_at', today())->count(),
            'teams_created_this_week' => Team::whereBetween('created_at', [
                now()->startOfWeek(), 
                now()->endOfWeek()
            ])->count(),
            'teams_created_this_month' => Team::whereMonth('created_at', now()->month)->count(),
        ];
    }
}
