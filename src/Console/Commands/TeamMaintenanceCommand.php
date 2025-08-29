<?php

declare(strict_types=1);

namespace Litepie\Teams\Console\Commands;

use Illuminate\Console\Command;
use Litepie\Teams\Models\Team;
use Litepie\Teams\Models\TeamMember;

class TeamMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:maintenance 
                            {action : The maintenance action to perform (cleanup, archive-inactive, remove-expired-invitations)}
                            {--dry-run : Show what would be done without making changes}
                            {--days=30 : Number of days for time-based operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform maintenance operations on teams';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $dryRun = $this->option('dry-run');
        $days = (int) ($this->option('days') ?? 30);

        $this->info("Performing teams maintenance: {$action}");

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode - no changes will be made');
        }

        try {
            switch ($action) {
                case 'cleanup':
                    return $this->performCleanup($dryRun);
                
                case 'archive-inactive':
                    return $this->archiveInactiveTeams($days, $dryRun);
                
                case 'remove-expired-invitations':
                    return $this->removeExpiredInvitations($days, $dryRun);
                
                default:
                    $this->error("Unknown action: {$action}");
                    $this->line('Available actions: cleanup, archive-inactive, remove-expired-invitations');
                    return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Maintenance operation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Perform general cleanup operations.
     */
    protected function performCleanup(bool $dryRun): int
    {
        $this->info('Performing general cleanup...');

        // Clean up soft-deleted teams older than 30 days
        $deletedTeams = Team::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(30))
            ->count();

        if ($deletedTeams > 0) {
            $this->line("Found {$deletedTeams} soft-deleted teams to permanently delete");
            
            if (!$dryRun) {
                Team::onlyTrashed()
                    ->where('deleted_at', '<', now()->subDays(30))
                    ->forceDelete();
                $this->info("✅ Permanently deleted {$deletedTeams} teams");
            }
        } else {
            $this->line('No soft-deleted teams found for cleanup');
        }

        return Command::SUCCESS;
    }

    /**
     * Archive inactive teams.
     */
    protected function archiveInactiveTeams(int $days, bool $dryRun): int
    {
        $this->info("Archiving teams inactive for {$days} days...");

        $inactiveTeams = Team::where('status', 'active')
            ->where('updated_at', '<', now()->subDays($days))
            ->whereDoesntHave('members', function ($query) use ($days) {
                $query->where('last_activity_at', '>', now()->subDays($days));
            })
            ->get();

        $count = $inactiveTeams->count();

        if ($count > 0) {
            $this->line("Found {$count} inactive teams to archive");
            
            if (!$dryRun) {
                foreach ($inactiveTeams as $team) {
                    $team->update(['status' => 'archived']);
                }
                $this->info("✅ Archived {$count} inactive teams");
            }
        } else {
            $this->line('No inactive teams found for archiving');
        }

        return Command::SUCCESS;
    }

    /**
     * Remove expired invitations.
     */
    protected function removeExpiredInvitations(int $days, bool $dryRun): int
    {
        $this->info("Removing invitations older than {$days} days...");

        $expiredInvitations = \Litepie\Teams\Models\TeamInvitation::where('status', 'pending')
            ->where('created_at', '<', now()->subDays($days))
            ->count();

        if ($expiredInvitations > 0) {
            $this->line("Found {$expiredInvitations} expired invitations to remove");
            
            if (!$dryRun) {
                \Litepie\Teams\Models\TeamInvitation::where('status', 'pending')
                    ->where('created_at', '<', now()->subDays($days))
                    ->delete();
                $this->info("✅ Removed {$expiredInvitations} expired invitations");
            }
        } else {
            $this->line('No expired invitations found for removal');
        }

        return Command::SUCCESS;
    }
}
