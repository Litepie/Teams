<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Teams Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the Litepie Teams
    | package. You can customize various aspects of team management,
    | permissions, workflows, and integrations.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Eloquent models used by the Teams package.
    |
    */
    'models' => [
        'team' => \Litepie\Teams\Models\Team::class,
        'team_member' => \Litepie\Teams\Models\TeamMember::class,
        'team_invitation' => \Litepie\Teams\Models\TeamInvitation::class,
        'team_role' => \Litepie\Teams\Models\TeamRole::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database table names and relationships.
    |
    */
    'database' => [
        'tables' => [
            'teams' => 'teams',
            'team_members' => 'team_members',
            'team_invitations' => 'team_invitations',
            'team_roles' => 'team_roles',
        ],
        'foreign_keys' => [
            'user_id' => 'user_id',
            'team_id' => 'team_id',
            'tenant_id' => 'tenant_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the Teams package.
    |
    */
    'features' => [
        'tenancy' => env('TEAMS_TENANCY_ENABLED', true),
        'workflows' => env('TEAMS_WORKFLOWS_ENABLED', true),
        'file_management' => env('TEAMS_FILES_ENABLED', true),
        'real_time_events' => env('TEAMS_REALTIME_ENABLED', true),
        'team_analytics' => env('TEAMS_ANALYTICS_ENABLED', true),
        'team_templates' => env('TEAMS_TEMPLATES_ENABLED', true),
        'bulk_operations' => env('TEAMS_BULK_OPS_ENABLED', true),
        'audit_logging' => env('TEAMS_AUDIT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Configuration
    |--------------------------------------------------------------------------
    |
    | Configure team-based permissions and roles.
    |
    */
    'permissions' => [
        'auto_create' => true,
        'middleware' => ['team.member'],
        'cache_permissions' => true,
        'cache_ttl' => 3600, // 1 hour
        
        'default_permissions' => [
            'view_team',
            'view_team_members',
            'view_team_files',
        ],
        
        'admin_permissions' => [
            'manage_team',
            'manage_team_members',
            'manage_team_roles',
            'manage_team_files',
            'manage_team_settings',
            'delete_team',
        ],
        
        'member_permissions' => [
            'view_team',
            'view_team_members',
            'view_team_files',
            'upload_team_files',
            'create_team_content',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Team Roles Configuration
    |--------------------------------------------------------------------------
    |
    | Define default team roles and their hierarchies.
    |
    */
    'roles' => [
        'default_roles' => [
            'owner' => [
                'name' => 'Owner',
                'level' => 100,
                'permissions' => ['*'],
                'is_admin' => true,
                'can_delete_team' => true,
            ],
            'admin' => [
                'name' => 'Administrator',
                'level' => 90,
                'permissions' => ['manage_team', 'manage_team_members', 'manage_team_files'],
                'is_admin' => true,
                'can_delete_team' => false,
            ],
            'manager' => [
                'name' => 'Manager',
                'level' => 70,
                'permissions' => ['manage_team_members', 'manage_team_files', 'view_team_analytics'],
                'is_admin' => false,
                'can_delete_team' => false,
            ],
            'member' => [
                'name' => 'Member',
                'level' => 50,
                'permissions' => ['view_team', 'view_team_members', 'upload_files'],
                'is_admin' => false,
                'can_delete_team' => false,
            ],
            'viewer' => [
                'name' => 'Viewer',
                'level' => 10,
                'permissions' => ['view_team'],
                'is_admin' => false,
                'can_delete_team' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Team Invitations Configuration
    |--------------------------------------------------------------------------
    |
    | Configure team invitation behavior and limits.
    |
    */
    'invitations' => [
        'enabled' => true,
        'expires_after_days' => 7,
        'max_pending_per_team' => 50,
        'auto_accept_same_domain' => false,
        'require_approval' => false,
        'send_notifications' => true,
        'resend_limit' => 3,
        'resend_cooldown_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Team Limits Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default limits for teams and members.
    |
    */
    'limits' => [
        'max_teams_per_user' => env('TEAMS_MAX_PER_USER', 10),
        'max_members_per_team' => env('TEAMS_MAX_MEMBERS', 100),
        'max_files_per_team' => env('TEAMS_MAX_FILES', 1000),
        'max_file_size_mb' => env('TEAMS_MAX_FILE_SIZE', 100),
        'max_storage_per_team_gb' => env('TEAMS_MAX_STORAGE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow Configuration
    |--------------------------------------------------------------------------
    |
    | Configure team workflow states and transitions.
    |
    */
    'workflows' => [
        'enabled' => true,
        'default_workflow' => 'team_lifecycle',
        
        'states' => [
            'draft' => ['name' => 'Draft', 'color' => '#6b7280'],
            'active' => ['name' => 'Active', 'color' => '#10b981'],
            'suspended' => ['name' => 'Suspended', 'color' => '#f59e0b'],
            'archived' => ['name' => 'Archived', 'color' => '#6b7280'],
        ],
        
        'transitions' => [
            'activate' => ['from' => ['draft'], 'to' => 'active'],
            'suspend' => ['from' => ['active'], 'to' => 'suspended'],
            'resume' => ['from' => ['suspended'], 'to' => 'active'],
            'archive' => ['from' => ['active', 'suspended'], 'to' => 'archived'],
            'restore' => ['from' => ['archived'], 'to' => 'active'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configure team file management settings.
    |
    */
    'files' => [
        'enabled' => true,
        'disk' => env('TEAMS_FILES_DISK', 'local'),
        'upload_path' => 'teams/{team_id}',
        
        'collections' => [
            'avatars' => [
                'disk' => 'public',
                'path' => 'teams/{team_id}/avatars',
                'max_files' => 1,
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/gif'],
                'max_size' => 5 * 1024 * 1024, // 5MB
            ],
            'documents' => [
                'disk' => 'local',
                'path' => 'teams/{team_id}/documents',
                'max_files' => 100,
                'allowed_mimes' => ['application/pdf', 'application/msword', 'text/plain'],
                'max_size' => 50 * 1024 * 1024, // 50MB
            ],
            'images' => [
                'disk' => 'public',
                'path' => 'teams/{team_id}/images',
                'max_files' => 50,
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'max_size' => 10 * 1024 * 1024, // 10MB
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure team analytics and metrics collection.
    |
    */
    'analytics' => [
        'enabled' => true,
        'track_member_activity' => true,
        'track_file_usage' => true,
        'track_collaboration_metrics' => true,
        'retention_days' => 90,
        
        'metrics' => [
            'team_activity',
            'member_engagement',
            'file_downloads',
            'collaboration_score',
            'workflow_efficiency',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for teams data.
    |
    */
    'cache' => [
        'enabled' => true,
        'store' => env('TEAMS_CACHE_STORE', 'redis'),
        'prefix' => 'teams',
        'ttl' => [
            'teams' => 3600,          // 1 hour
            'members' => 1800,        // 30 minutes
            'permissions' => 3600,    // 1 hour
            'analytics' => 7200,      // 2 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    |
    | Configure event handling and broadcasting.
    |
    */
    'events' => [
        'enabled' => true,
        'broadcast' => env('TEAMS_BROADCAST_EVENTS', true),
        'queue' => env('TEAMS_EVENTS_QUEUE', 'default'),
        
        'listeners' => [
            'team_created' => [
                \Litepie\Teams\Listeners\CreateDefaultTeamRoles::class,
                \Litepie\Teams\Listeners\SendTeamCreatedNotification::class,
            ],
            'member_joined' => [
                \Litepie\Teams\Listeners\SendMemberWelcomeNotification::class,
                \Litepie\Teams\Listeners\UpdateTeamMetrics::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure team notifications.
    |
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['mail', 'database', 'broadcast'],
        'queue' => env('TEAMS_NOTIFICATIONS_QUEUE', 'default'),
        
        'types' => [
            'team_invitation' => [
                'channels' => ['mail', 'database'],
                'template' => 'teams::notifications.invitation',
            ],
            'member_joined' => [
                'channels' => ['database', 'broadcast'],
                'template' => 'teams::notifications.member_joined',
            ],
            'team_updated' => [
                'channels' => ['database', 'broadcast'],
                'template' => 'teams::notifications.team_updated',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API endpoints and resources.
    |
    */
    'api' => [
        'enabled' => true,
        'prefix' => 'api/v1',
        'middleware' => ['api', 'tenant.required'],
        'rate_limiting' => true,
        'rate_limit' => '60:1', // 60 requests per minute
        
        'resources' => [
            'teams' => \Litepie\Teams\Http\Resources\TeamResource::class,
            'members' => \Litepie\Teams\Http\Resources\TeamMemberResource::class,
            'invitations' => \Litepie\Teams\Http\Resources\TeamInvitationResource::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings for teams.
    |
    */
    'security' => [
        'audit_team_changes' => true,
        'require_2fa_for_admin' => false,
        'ip_whitelist_enabled' => false,
        'session_timeout_minutes' => 120,
        
        'validation_rules' => [
            'team_name' => 'required|string|min:3|max:255',
            'team_description' => 'nullable|string|max:1000',
            'team_type' => 'required|string|in:project,department,organization,community',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Configuration
    |--------------------------------------------------------------------------
    |
    | Configure team templates for quick setup.
    |
    */
    'templates' => [
        'enabled' => true,
        'default_templates' => [
            'development_team' => [
                'name' => 'Development Team',
                'description' => 'Template for software development teams',
                'roles' => ['owner', 'admin', 'developer', 'tester'],
                'permissions' => ['manage_code', 'review_code', 'deploy'],
                'workflows' => ['development_lifecycle'],
            ],
            'marketing_team' => [
                'name' => 'Marketing Team',
                'description' => 'Template for marketing teams',
                'roles' => ['owner', 'admin', 'marketer', 'content_creator'],
                'permissions' => ['manage_campaigns', 'create_content', 'analyze_metrics'],
                'workflows' => ['campaign_lifecycle'],
            ],
        ],
    ],
];
