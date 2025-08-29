<?php

declare(strict_types=1);

namespace Litepie\Teams\Contracts;

use Litepie\Teams\Models\TeamInvitation;
use Litepie\Teams\Models\Team;

/**
 * TeamInvitationRepository Contract
 * 
 * Interface for team invitation repository operations.
 */
interface TeamInvitationRepository
{
    /**
     * Find an invitation by ID.
     */
    public function find(int $id): ?TeamInvitation;

    /**
     * Find an invitation by ID or fail.
     */
    public function findOrFail(int $id): TeamInvitation;

    /**
     * Find an invitation by token.
     */
    public function findByToken(string $token): ?TeamInvitation;

    /**
     * Create a new invitation.
     */
    public function create(array $data): TeamInvitation;

    /**
     * Update an invitation.
     */
    public function update(TeamInvitation $invitation, array $data): TeamInvitation;

    /**
     * Delete an invitation.
     */
    public function delete(TeamInvitation $invitation): bool;

    /**
     * Get invitations for a team.
     */
    public function getInvitationsForTeam(int $teamId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get invitations by email.
     */
    public function getInvitationsByEmail(string $email): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get invitations by status.
     */
    public function getInvitationsByStatus(string $status): \Illuminate\Database\Eloquent\Collection;

    /**
     * Create team invitation.
     */
    public function createTeamInvitation(Team $team, string $email, string $role = 'member', $invitedBy = null): TeamInvitation;

    /**
     * Accept invitation.
     */
    public function acceptInvitation(TeamInvitation $invitation, int $userId): bool;

    /**
     * Decline invitation.
     */
    public function declineInvitation(TeamInvitation $invitation): bool;

    /**
     * Get expired invitations.
     */
    public function getExpiredInvitations(int $daysOld = 30): \Illuminate\Database\Eloquent\Collection;
}
