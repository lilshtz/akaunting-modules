<?php

namespace Modules\PaypalSync\Console\Commands;

use Illuminate\Console\Command;
use Modules\PaypalSync\Models\PaypalSyncSettings;
use Modules\PaypalSync\Services\PaypalService;
use Modules\PaypalSync\Services\TransactionSyncService;

class SyncPaypalTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal-sync:sync {--company= : Company ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync PayPal transactions for all enabled companies';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $query = PaypalSyncSettings::where('enabled', true);

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        $settings = $query->get();

        if ($settings->isEmpty()) {
            $this->warn('No enabled PayPal Sync configurations found.');
            return Command::SUCCESS;
        }

        foreach ($settings as $setting) {
            $this->info("Syncing PayPal for company {$setting->company_id}...");

            try {
                $paypalService = new PaypalService($setting);
                $syncService = new TransactionSyncService($paypalService, $setting);
                $result = $syncService->sync();

                $this->info("Imported: {$result['imported']}, Skipped: {$result['skipped']}, Errors: {$result['errors']}");

                // Attempt to match transactions to invoices
                $matched = $syncService->matchToInvoices();
                if ($matched > 0) {
                    $this->info("Matched {$matched} transactions to invoices.");
                }
            } catch (\Exception $e) {
                $this->error("Error syncing company {$setting->company_id}: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
