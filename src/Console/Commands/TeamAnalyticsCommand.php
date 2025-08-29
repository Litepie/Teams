<?php

declare(strict_types=1);

namespace Litepie\Teams\Console\Commands;

use Illuminate\Console\Command;
use Litepie\Teams\Models\Team;
use Litepie\Teams\Models\TeamMember;

class TeamAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:analytics 
                            {--team= : Specific team ID to analyze}
                            {--format=table : Output format (table, json)}
                            {--export= : Export to file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate teams analytics and statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $teamId = $this->option('team');
        $format = $this->option('format') ?? 'table';
        $exportPath = $this->option('export');

        $this->info('Generating Teams Analytics...');

        try {
            if ($teamId) {
                $analytics = $this->getTeamAnalytics($teamId);
            } else {
                $analytics = $this->getGlobalAnalytics();
            }

            if ($format === 'json') {
                $output = json_encode($analytics, JSON_PRETTY_PRINT);
                $this->line($output);
            } else {
                $this->displayTableFormat($analytics);
            }

            if ($exportPath) {
                $this->exportAnalytics($analytics, $exportPath, $format);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to generate analytics: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Get analytics for a specific team.
     */
    protected function getTeamAnalytics(string $teamId): array
    {
        $team = Team::findOrFail($teamId);
        
        return [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'total_members' => $team->members()->count(),
            'active_members' => $team->members()->where('status', 'active')->count(),
            'pending_invitations' => $team->invitations()->where('status', 'pending')->count(),
            'created_at' => $team->created_at->format('Y-m-d H:i:s'),
            'status' => $team->status,
        ];
    }

    /**
     * Get global analytics across all teams.
     */
    protected function getGlobalAnalytics(): array
    {
        return [
            'total_teams' => Team::count(),
            'active_teams' => Team::where('status', 'active')->count(),
            'total_members' => TeamMember::count(),
            'active_members' => TeamMember::where('status', 'active')->count(),
            'teams_created_today' => Team::whereDate('created_at', today())->count(),
            'teams_created_this_week' => Team::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'teams_created_this_month' => Team::whereMonth('created_at', now()->month)->count(),
        ];
    }

    /**
     * Display analytics in table format.
     */
    protected function displayTableFormat(array $analytics): void
    {
        $headers = ['Metric', 'Value'];
        $rows = [];

        foreach ($analytics as $key => $value) {
            $rows[] = [ucfirst(str_replace('_', ' ', $key)), $value];
        }

        $this->table($headers, $rows);
    }

    /**
     * Export analytics to file.
     */
    protected function exportAnalytics(array $analytics, string $path, string $format): void
    {
        $content = $format === 'json' 
            ? json_encode($analytics, JSON_PRETTY_PRINT)
            : $this->formatAsCsv($analytics);

        file_put_contents($path, $content);
        $this->info("Analytics exported to: {$path}");
    }

    /**
     * Format analytics as CSV.
     */
    protected function formatAsCsv(array $analytics): string
    {
        $csv = "Metric,Value\n";
        foreach ($analytics as $key => $value) {
            $csv .= ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }
        return $csv;
    }
}
