<?php

declare(strict_types=1);

namespace Litepie\Teams\Middleware;

use Closure;
use Illuminate\Http\Request;
use Litepie\Teams\Models\Team;

/**
 * TeamPermissionMiddleware
 * 
 * Ensures the authenticated user has the required permission for the team.
 */
class TeamPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @param  string|null  $teamParam
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $teamParam = 'team')
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Get team from route parameter
        $teamId = $request->route($teamParam);
        
        if (!$teamId) {
            return response()->json(['error' => 'Team not specified'], 400);
        }

        $team = Team::find($teamId);
        
        if (!$team) {
            return response()->json(['error' => 'Team not found'], 404);
        }

        // Check if user has permission for this team
        if (!$this->userHasPermission($user, $team, $permission)) {
            return response()->json(['error' => 'Access denied. Insufficient permissions.'], 403);
        }

        // Add team to request for easy access in controllers
        $request->attributes->set('team', $team);

        return $next($request);
    }

    /**
     * Check if user has the required permission for the team.
     */
    protected function userHasPermission($user, Team $team, string $permission): bool
    {
        // Get user's membership in the team
        $membership = $team->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return false;
        }

        // Team owner has all permissions
        if ($team->owner_id === $user->id) {
            return true;
        }

        // Check specific permissions based on role or custom permissions
        $userRole = $membership->role ?? 'member';
        
        // Define role-based permissions
        $rolePermissions = [
            'owner' => ['*'], // All permissions
            'admin' => [
                'manage_members', 'manage_settings', 'view_team', 
                'edit_team', 'invite_members', 'remove_members'
            ],
            'moderator' => [
                'view_team', 'invite_members', 'manage_content'
            ],
            'member' => [
                'view_team'
            ],
        ];

        $allowedPermissions = $rolePermissions[$userRole] ?? [];

        // Check if user has wildcard permission or specific permission
        return in_array('*', $allowedPermissions) || in_array($permission, $allowedPermissions);
    }
}
