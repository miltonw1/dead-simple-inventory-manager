<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire subscriptions that have passed their end date';

    public function handle(): int
    {
        $expired = Subscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->update(['status' => 'expired']);

        if ($expired > 0) {
            Log::info("Expired {$expired} subscription(s).");
            $this->info("Expired {$expired} subscription(s).");
        } else {
            $this->info('No subscriptions to expire.');
        }

        return Command::SUCCESS;
    }
}
