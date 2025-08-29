<?php

declare(strict_types=1);

namespace Litepie\Teams\Console\Commands;

use Illuminate\Console\Command;
use Litepie\Teams\Actions\CreateTeamAction;

class CreateTeamCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:create 
                            {name : The name of the team}
                            {--description= : The description of the team}
                            {--owner= : The owner email of the team}
                            {--type=default : The type of the team}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new team';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $description = $this->option('description');
        $ownerEmail = $this->option('owner');
        $type = $this->option('type') ?? 'default';

        if (!$name) {
            $this->error('Team name is required.');
            return Command::FAILURE;
        }

        try {
            $teamData = [
                'name' => $name,
                'description' => $description,
                'type' => $type,
                'status' => 'active',
            ];

            if ($ownerEmail) {
                $user = \App\Models\User::where('email', $ownerEmail)->first();
                if (!$user) {
                    $this->error("User with email {$ownerEmail} not found.");
                    return Command::FAILURE;
                }
                $teamData['owner_id'] = $user->id;
            }

            $action = app(CreateTeamAction::class);
            $team = $action->execute($teamData);

            $this->info("✅ Team '{$name}' created successfully with ID: {$team->id}");
            
            if ($description) {
                $this->line("Description: {$description}");
            }
            
            if ($ownerEmail) {
                $this->line("Owner: {$ownerEmail}");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create team: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
