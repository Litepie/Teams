<?php

declare(strict_types=1);

namespace Litepie\Teams\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallTeamsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:install 
                            {--force : Overwrite existing files}
                            {--config : Publish configuration files}
                            {--migrations : Publish migration files}
                            {--views : Publish view files}
                            {--lang : Publish language files}
                            {--assets : Publish asset files}
                            {--all : Publish all files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Teams package components';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Litepie Teams Package...');

        $publishConfig = $this->option('config') || $this->option('all');
        $publishMigrations = $this->option('migrations') || $this->option('all');
        $publishViews = $this->option('views') || $this->option('all');
        $publishLang = $this->option('lang') || $this->option('all');
        $publishAssets = $this->option('assets') || $this->option('all');
        $force = $this->option('force');

        // If no specific options are provided, install basic components
        if (!$this->option('config') && !$this->option('migrations') && 
            !$this->option('views') && !$this->option('lang') && 
            !$this->option('assets') && !$this->option('all')) {
            $publishConfig = true;
            $publishMigrations = true;
        }

        // Publish configuration
        if ($publishConfig) {
            $this->publishConfig($force);
        }

        // Publish migrations
        if ($publishMigrations) {
            $this->publishMigrations($force);
        }

        // Publish views
        if ($publishViews) {
            $this->publishViews($force);
        }

        // Publish language files
        if ($publishLang) {
            $this->publishLang($force);
        }

        // Publish assets
        if ($publishAssets) {
            $this->publishAssets($force);
        }

        $this->newLine();
        $this->info('✅ Teams package installation completed!');
        
        if ($publishMigrations) {
            $this->newLine();
            $this->comment('Next steps:');
            $this->line('1. Run: php artisan migrate');
            $this->line('2. Configure your teams settings in config/teams.php');
        }

        return Command::SUCCESS;
    }

    /**
     * Publish configuration files.
     */
    protected function publishConfig(bool $force = false): void
    {
        $this->line('Publishing configuration files...');
        
        $params = ['--tag' => 'teams-config'];
        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(bool $force = false): void
    {
        $this->line('Publishing migration files...');
        
        $params = ['--tag' => 'teams-migrations'];
        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    /**
     * Publish view files.
     */
    protected function publishViews(bool $force = false): void
    {
        $this->line('Publishing view files...');
        
        $params = ['--tag' => 'teams-views'];
        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    /**
     * Publish language files.
     */
    protected function publishLang(bool $force = false): void
    {
        $this->line('Publishing language files...');
        
        $params = ['--tag' => 'teams-lang'];
        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    /**
     * Publish asset files.
     */
    protected function publishAssets(bool $force = false): void
    {
        $this->line('Publishing asset files...');
        
        $params = ['--tag' => 'teams-assets'];
        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }
}
