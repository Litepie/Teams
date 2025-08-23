<?php

declare(strict_types=1);

namespace Litepie\Teams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TeamResource
 * 
 * API resource for team data transformation.
 */
class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'visibility' => $this->settings['visibility'] ?? 'private',
            'member_count' => $this->whenLoaded('members', fn() => $this->members->count()),
            'max_members' => $this->settings['max_members'] ?? null,
            'allow_invitations' => $this->settings['allow_invitations'] ?? true,
            'timezone' => $this->settings['timezone'] ?? 'UTC',
            'language' => $this->settings['language'] ?? 'en',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'activated_at' => $this->activated_at,
            'suspended_at' => $this->suspended_at,
            'archived_at' => $this->archived_at,
            
            // Relationships
            'owner' => $this->whenLoaded('owner', fn() => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
                'avatar' => $this->owner->avatar ?? null,
            ]),
            
            'members' => $this->whenLoaded('members', fn() => 
                $this->members->map(fn($member) => [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'status' => $member->status,
                    'joined_at' => $member->created_at,
                    'permissions' => $member->permissions,
                    'user' => $this->when($member->relationLoaded('user'), [
                        'id' => $member->user->id,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'avatar' => $member->user->avatar ?? null,
                    ]),
                ])
            ),
            
            'invitations' => $this->whenLoaded('invitations', fn() =>
                $this->invitations->map(fn($invitation) => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'status' => $invitation->status,
                    'expires_at' => $invitation->expires_at,
                    'invited_at' => $invitation->created_at,
                ])
            ),
            
            'files' => $this->whenLoaded('files', fn() =>
                $this->files->map(fn($file) => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'size' => $file->size,
                    'type' => $file->type,
                    'url' => $file->url,
                    'uploaded_at' => $file->created_at,
                ])
            ),
            
            // Permissions for current user
            'permissions' => $this->when(auth()->check(), function () {
                $user = auth()->user();
                return [
                    'can_view' => $this->userHasPermission($user, 'view_team'),
                    'can_update' => $this->userHasPermission($user, 'update_team'),
                    'can_delete' => $this->userHasPermission($user, 'delete_team'),
                    'can_manage_members' => $this->userHasPermission($user, 'manage_team_members'),
                    'can_invite_members' => $this->userHasPermission($user, 'invite_team_member'),
                    'is_owner' => $this->user_id === $user->id,
                    'is_member' => $this->members->where('user_id', $user->id)->isNotEmpty(),
                ];
            }),
            
            // Additional metadata
            'meta' => [
                'workflow_state' => $this->status,
                'can_transition_to' => $this->when(auth()->check(), function () {
                    return $this->getAvailableTransitions(auth()->user());
                }),
                'last_activity' => $this->updated_at,
                'tenant_id' => $this->tenant_id,
            ],
        ];
    }
}
