<?php

namespace App\Console\Commands;

use App\Services\SeasonSwitchingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSeasonSwitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bell:check-season-switch 
                            {--force : Force season switch regardless of current date}
                            {--season= : Manually switch to specific season (summer/winter)}
                            {--dry-run : Show what would happen without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and perform automatic season switching for bell schedules';

    /**
     * Season switching service
     */
    private SeasonSwitchingService $seasonService;

    /**
     * Create a new command instance.
     */
    public function __construct(SeasonSwitchingService $seasonService)
    {
        parent::__construct();
        $this->seasonService = $seasonService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Checking season switching for bell schedules...');
        $this->newLine();

        try {
            // Handle manual season switch
            if ($this->option('season')) {
                return $this->handleManualSeasonSwitch();
            }

            // Handle dry run
            if ($this->option('dry-run')) {
                return $this->handleDryRun();
            }

            // Handle force switch
            if ($this->option('force')) {
                return $this->handleForceSwitch();
            }

            // Normal automatic check
            return $this->handleAutomaticCheck();

        } catch (\Exception $e) {
            $this->error('❌ Error during season switch check: ' . $e->getMessage());
            Log::error('Season switch command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Handle manual season switch
     */
    private function handleManualSeasonSwitch(): int
    {
        $season = $this->option('season');
        
        if (!in_array($season, ['summer', 'winter'])) {
            $this->error('❌ Invalid season. Use "summer" or "winter".');
            return Command::FAILURE;
        }

        $this->warn("⚠️  Manually switching to {$season} season...");
        
        if (!$this->confirm('Are you sure you want to manually switch seasons?')) {
            $this->info('Season switch cancelled.');
            return Command::SUCCESS;
        }

        $result = $this->seasonService->manualSeasonSwitch($season);
        
        $this->displaySwitchResults($result);
        
        return Command::SUCCESS;
    }

    /**
     * Handle dry run
     */
    private function handleDryRun(): int
    {
        $this->info('🔍 Dry run mode - no changes will be made');
        $this->newLine();

        $currentSeason = $this->seasonService->getCurrentSeason();
        $seasonInfo = $this->seasonService->getSeasonInfo();
        
        $this->displayCurrentSeasonInfo($seasonInfo);
        
        // Check what would happen
        $this->info('📋 What would happen:');
        
        if ($this->seasonService->hasManualOverride()) {
            $this->warn('  • Manual season override is active');
            $this->info('  • Automatic switching is disabled');
        } else {
            $this->info("  • Current season: {$currentSeason}");
            $this->info("  • Next switch: {$seasonInfo['next_switch']['formatted']} ({$seasonInfo['next_switch']['days_until']} days)");
            
            if ($seasonInfo['next_switch']['days_until'] == 0) {
                $this->warn('  • Season switch would occur today!');
            }
        }
        
        return Command::SUCCESS;
    }

    /**
     * Handle force switch
     */
    private function handleForceSwitch(): int
    {
        $this->warn('⚠️  Force switching to current season...');
        
        $currentSeason = $this->seasonService->getCurrentSeason();
        $result = $this->seasonService->manualSeasonSwitch($currentSeason);
        
        $this->displaySwitchResults($result);
        
        return Command::SUCCESS;
    }

    /**
     * Handle automatic check
     */
    private function handleAutomaticCheck(): int
    {
        // Display current season info
        $seasonInfo = $this->seasonService->getSeasonInfo();
        $this->displayCurrentSeasonInfo($seasonInfo);

        // Check if manual override is active
        if ($this->seasonService->hasManualOverride()) {
            $this->warn('⚠️  Manual season override is active - automatic switching disabled');
            $this->info('Use --force to override or clear the manual override first');
            return Command::SUCCESS;
        }

        // Perform automatic check and switch
        $result = $this->seasonService->checkAndSwitchSeason();
        
        if ($result['switched']) {
            $this->info('✅ Season switch performed!');
            $this->displaySwitchResults($result);
        } else {
            $this->info('✅ No season switch needed');
            $this->info("Next switch: {$seasonInfo['next_switch']['formatted']} ({$seasonInfo['next_switch']['days_until']} days)");
        }
        
        return Command::SUCCESS;
    }

    /**
     * Display current season information
     */
    private function displayCurrentSeasonInfo(array $seasonInfo): void
    {
        $this->info('📅 Current Season Information:');
        $this->table(
            ['Property', 'Value'],
            [
                ['Season', ucfirst($seasonInfo['season'])],
                ['Name', $seasonInfo['name']],
                ['Description', $seasonInfo['description']],
                ['Date Range', $seasonInfo['date_range']['start_formatted'] . ' - ' . $seasonInfo['date_range']['end_formatted']],
                ['Manual Override', $this->seasonService->hasManualOverride() ? 'Yes' : 'No']
            ]
        );
        $this->newLine();
    }

    /**
     * Display season switch results
     */
    private function displaySwitchResults(array $result): void
    {
        $this->newLine();
        $this->info('📊 Season Switch Results:');
        
        $tableData = [
            ['From Season', ucfirst($result['from_season'] ?? 'N/A')],
            ['To Season', ucfirst($result['to_season'])],
            ['Switch Date', $result['switch_date']],
            ['Affected Schedules', $result['affected_schedules']],
            ['Notifications Sent', $result['notifications_sent']],
            ['Manual Switch', isset($result['manual']) && $result['manual'] ? 'Yes' : 'No']
        ];
        
        $this->table(['Property', 'Value'], $tableData);
        
        if ($result['affected_schedules'] > 0) {
            $this->info("✅ Successfully updated {$result['affected_schedules']} bell schedules");
        }
        
        if ($result['notifications_sent'] > 0) {
            $this->info("📱 Sent {$result['notifications_sent']} notifications");
        }
        
        $this->newLine();
        $this->info('🎯 Season switch completed successfully!');
    }
}