<?php

declare(strict_types=1);

namespace Litepie\Teams\Contracts;

use Litepie\Teams\Models\Team;

/**
 * TeamRepository Contract
 * 
 * Interface for team repository operations.
 */
interface TeamRepository
{
    /**
     * Find a team by ID.
     */
    public function find(int $id): ?Team;

    /**
     * Find a team by ID or fail.
     */
    public function findOrFail(int $id): Team;

    /**
     * Create a new team.
     */
    public function create(array $data): Team;

    /**
     * Update a team.
     */
    public function update(Team $team, array $data): Team;

    /**
     * Delete a team.
     */
    public function delete(Team $team): bool;

    /**
     * Get teams for a user.
     */
    public function getTeamsForUser(int $userId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get teams by status.
     */
    public function getByStatus(string $status): \Illuminate\Database\Eloquent\Collection;

    /**
     * Search teams by name.
     */
    public function searchByName(string $name): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get paginated teams.
     */
    public function paginate(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get team statistics.
     */
    public function getStatistics(): array;
}
