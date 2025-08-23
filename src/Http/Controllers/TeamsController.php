<?php

declare(strict_types=1);

namespace Litepie\Teams\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Litepie\Teams\Actions\CreateTeamAction;
use Litepie\Teams\Actions\UpdateTeamAction;
use Litepie\Teams\Actions\ActivateTeamAction;
use Litepie\Teams\Actions\SuspendTeamAction;
use Litepie\Teams\Actions\ArchiveTeamAction;
use Litepie\Teams\Actions\RestoreTeamAction;
use Litepie\Teams\Http\Requests\CreateTeamRequest;
use Litepie\Teams\Http\Requests\UpdateTeamRequest;
use Litepie\Teams\Http\Resources\TeamResource;
use Litepie\Teams\Http\Resources\TeamsCollection;
use Litepie\Teams\Models\Team;

/**
 * TeamsController
 * 
 * Handles HTTP requests for team management operations.
 */
class TeamsController extends Controller
{
    /**
     * Display a listing of teams.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $teams = Team::query()
            ->tenantScope()
            ->with(['owner', 'members'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $sortField = $request->sort;
                $sortDirection = $request->boolean('desc') ? 'desc' : 'asc';
                $query->orderBy($sortField, $sortDirection);
            }, function ($query) {
                $query->latest();
            })
            ->paginate($request->integer('per_page', 15));

        return TeamsCollection::make($teams);
    }

    /**
     * Store a newly created team.
     */
    public function store(CreateTeamRequest $request): JsonResponse
    {
        $result = app(CreateTeamAction::class)
            ->setUser($request->user())
            ->execute($request->validated());

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => TeamResource::make($result['team']),
            ], 201);
        }

        return response()->json([
            'message' => 'Failed to create team',
            'errors' => $result['errors'] ?? [],
        ], 422);
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $team->load(['owner', 'members.user', 'invitations']);

        return response()->json([
            'data' => TeamResource::make($team),
        ]);
    }

    /**
     * Update the specified team.
     */
    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $result = app(UpdateTeamAction::class)
            ->setUser($request->user())
            ->execute(array_merge($request->validated(), ['team_id' => $team->id]));

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => TeamResource::make($result['team']),
            ]);
        }

        return response()->json([
            'message' => 'Failed to update team',
            'errors' => $result['errors'] ?? [],
        ], 422);
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team): JsonResponse
    {
        $this->authorize('delete', $team);

        // Archive the team instead of hard delete
        $result = app(ArchiveTeamAction::class)
            ->setUser(request()->user())
            ->execute([
                'team_id' => $team->id,
                'archived_by' => request()->user()->id,
                'reason' => 'Team deleted via API',
            ]);

        if ($result['success']) {
            return response()->json([
                'message' => 'Team archived successfully',
            ]);
        }

        return response()->json([
            'message' => 'Failed to archive team',
            'errors' => $result['errors'] ?? [],
        ], 422);
    }

    /**
     * Activate a team.
     */
    public function activate(Team $team): JsonResponse
    {
        $this->authorize('manage', $team);

        $result = app(ActivateTeamAction::class)
            ->setUser(request()->user())
            ->execute([
                'team_id' => $team->id,
                'activated_by' => request()->user()->id,
            ]);

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => TeamResource::make($result['team']),
            ]);
        }

        return response()->json([
            'message' => 'Failed to activate team',
            'errors' => $result['errors'] ?? [],
        ], 422);
    }

    /**
     * Suspend a team.
     */
    public function suspend(Request $request, Team $team): JsonResponse
    {
        $this->authorize('manage', $team);

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'suspension_until' => ['nullable', 'date', 'after:now'],
        ]);

        $result = app(SuspendTeamAction::class)
            ->setUser(request()->user())
            ->execute([
                'team_id' => $team->id,
                'suspended_by' => request()->user()->id,
                'reason' => $request->reason,
                'suspension_until' => $request->suspension_until,
            ]);

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => TeamResource::make($result['team']),
            ]);
        }

        return response()->json([
            'message' => 'Failed to suspend team',
            'errors' => $result['errors'] ?? [],
        ], 422);
    }

    /**
     * Restore an archived team.
     */
    public function restore(Team $team): JsonResponse
    {
        $this->authorize('manage', $team);

        $result = app(RestoreTeamAction::class)
            ->setUser(request()->user())
            ->execute([
                'team_id' => $team->id,
                'restored_by' => request()->user()->id,
            ]);

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => TeamResource::make($result['team']),
            ]);
        }

        return response()->json([
            'message' => 'Failed to restore team',
            'errors' => $result['errors'] ?? [],
        ], 422);
    }

    /**
     * Get team statistics.
     */
    public function stats(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $stats = [
            'members_count' => $team->members()->active()->count(),
            'pending_invitations' => $team->invitations()->pending()->count(),
            'created_at' => $team->created_at,
            'last_activity' => $team->updated_at,
            'status' => $team->status,
            'files_count' => $team->files()->count(),
            'settings' => $team->settings,
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }
}
