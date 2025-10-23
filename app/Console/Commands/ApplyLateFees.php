<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentFee;

class ApplyLateFees extends Command
{
    protected $signature = 'fees:apply-late-fees {--dry-run}';
    protected $description = 'Calculate and apply late fees for overdue student fees';

    public function handle(): int
    {
        $config = config('fees.late_fee');
        if (empty($config) || !$config['enabled']) {
            $this->info('Late fee is disabled. Skipping.');
            return self::SUCCESS;
        }

        $graceDays = (int)($config['grace_days'] ?? 0);
        $type = strtolower($config['type'] ?? 'per_day');
        $perDay = (float)($config['amount_per_day'] ?? 0);
        $flat = (float)($config['flat_amount'] ?? 0);
        $max = $config['max_late_fee'] !== null ? (float)$config['max_late_fee'] : null;

        $fees = StudentFee::where('status', '!=', 'paid')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        $updated = 0;
        foreach ($fees as $fee) {
            $daysOverdue = now()->diffInDays($fee->due_date);
            $effectiveDays = max(0, $daysOverdue - $graceDays);

            $lateFee = 0.0;
            if ($type === 'per_day') {
                $lateFee = $effectiveDays * $perDay;
            } elseif ($type === 'flat') {
                $lateFee = $effectiveDays > 0 ? $flat : 0.0;
            }

            if ($max !== null) {
                $lateFee = min($lateFee, $max);
            }

            // No change needed
            if ((float)$fee->late_fee === (float)$lateFee) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("[DRY] Fee ID {$fee->id}: late_fee {$fee->late_fee} -> {$lateFee}");
            } else {
                $fee->update(['late_fee' => $lateFee]);
                $updated++;
            }
        }

        $this->info("Late fees updated: {$updated}");
        return self::SUCCESS;
    }
}