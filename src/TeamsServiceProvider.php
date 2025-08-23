<?php

declare(strict_types=1);

namespace Litepie\Teams;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Litepie\Teams\Console\Commands\InstallTeamsCommand;
use Litepie\Teams\Console\Commands\CreateTeamCommand;
use Litepie\Teams\Console\Commands\TeamAnalyticsCommand;
use Litepie\Teams\Console\Commands\TeamMaintenanceCommand;
use Litepie\Teams\Events\TeamCreated;
use Litepie\Teams\Events\MemberJoinedTeam;
use Litepie\Teams\Events\MemberLeftTeam;
use Litepie\Teams\Events\TeamUpdated;
use Litepie\Teams\Listeners\CreateDefaultTeamRoles;
use Litepie\Teams\Listeners\SendTeamCreatedNotification;
use Litepie\Teams\Listeners\SendMemberWelcomeNotification;
use Litepie\Teams\Listeners\UpdateTeamMetrics;
use Litepie\Teams\Middleware\TeamMemberMiddleware;
use Litepie\Teams\Middleware\TeamPermissionMiddleware;
use Litepie\Teams\Workflows\TeamWorkflow;

class TeamsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'teams');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'teams');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerMiddleware();
        $this->registerEventListeners();
        $this->registerWorkflows();
        $this->registerCommands();
        $this->registerPublishing();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/teams.php', 'teams');
        
        $this->app->singleton('teams', function ($app) {
            return new Teams();
        });

        $this->app->bind(
            \Litepie\Teams\Contracts\TeamRepository::class,
            \Litepie\Teams\Repositories\TeamRepository::class
        );

        $this->app->bind(
            \Litepie\Teams\Contracts\TeamMemberRepository::class,
            \Litepie\Teams\Repositories\TeamMemberRepository::class
        );

        $this->app->bind(
            \Litepie\Teams\Contracts\TeamInvitationRepository::class,
            \Litepie\Teams\Repositories\TeamInvitationRepository::class
        );
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('team.member', TeamMemberMiddleware::class);
        $router->aliasMiddleware('team.permission', TeamPermissionMiddleware::class);
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        if (! config('teams.events.enabled')) {
            return;
        }

        Event::listen(TeamCreated::class, CreateDefaultTeamRoles::class);
        Event::listen(TeamCreated::class, SendTeamCreatedNotification::class);
        Event::listen(MemberJoinedTeam::class, SendMemberWelcomeNotification::class);
        Event::listen(MemberJoinedTeam::class, UpdateTeamMetrics::class);
        Event::listen(MemberLeftTeam::class, UpdateTeamMetrics::class);
        Event::listen(TeamUpdated::class, UpdateTeamMetrics::class);
    }

    /**
     * Register workflows.
     */
    protected function registerWorkflows(): void
    {
        if (! config('teams.features.workflows')) {
            return;
        }

        if (class_exists(\Litepie\Flow\Facades\Flow::class)) {
            \Litepie\Flow\Facades\Flow::register('team_lifecycle', TeamWorkflow::create());
        }
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallTeamsCommand::class,
                CreateTeamCommand::class,
                TeamAnalyticsCommand::class,
                TeamMaintenanceCommand::class,
            ]);
        }
    }

    /**
     * Register publishing options.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/teams.php' => config_path('teams.php'),
            ], 'teams-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'teams-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/teams'),
            ], 'teams-views');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/teams'),
            ], 'teams-lang');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/teams'),
            ], 'teams-assets');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'teams',
            \Litepie\Teams\Contracts\TeamRepository::class,
            \Litepie\Teams\Contracts\TeamMemberRepository::class,
            \Litepie\Teams\Contracts\TeamInvitationRepository::class,
        ];
    }
}
