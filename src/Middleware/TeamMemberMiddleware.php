<?php

declare(strict_types=1);

namespace Litepie\Teams\Middleware;

use Closure;
use Illuminate\Http\Request;
use Litepie\Teams\Models\Team;

/**
 * TeamMemberMiddleware
 * 
 * Ensures the authenticated user is a member of the specified team.
 */
class TeamMemberMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $teamParam
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $teamParam = 'team')
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

        // Check if user is a member of the team
        $isMember = $team->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (!$isMember) {
            return response()->json(['error' => 'Access denied. You are not a member of this team.'], 403);
        }

        // Add team to request for easy access in controllers
        $request->attributes->set('team', $team);

        return $next($request);
    }
}
