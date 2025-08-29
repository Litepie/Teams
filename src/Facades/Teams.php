<?php

declare(strict_types=1);

namespace Litepie\Teams\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Teams Facade
 * 
 * @method static \Litepie\Teams\Models\Team createTeam(array $data)
 * @method static \Litepie\Teams\Models\Team|null getTeam(int $id)
 * @method static \Illuminate\Database\Eloquent\Collection getUserTeams(int $userId)
 * @method static \Litepie\Teams\Models\TeamMember addMember(\Litepie\Teams\Models\Team $team, int $userId, string $role = 'member')
 * @method static bool removeMember(\Litepie\Teams\Models\Team $team, int $userId)
 * @method static \Litepie\Teams\Models\TeamInvitation inviteUser(\Litepie\Teams\Models\Team $team, string $email, string $role = 'member', $invitedBy = null)
 * @method static bool isMember(\Litepie\Teams\Models\Team $team, int $userId)
 * @method static array getStatistics()
 * @method static mixed getConfig(string $key = null, $default = null)
 * 
 * @see \Litepie\Teams\Teams
 */
class Teams extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'teams';
    }
}
