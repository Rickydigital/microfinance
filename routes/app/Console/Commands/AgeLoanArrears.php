<?php

namespace App\Console\Commands;

use App\Services\ArrearsAgingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('loans:age-arrears {--date= : Business date in YYYY-MM-DD format}')]
#[Description('Calculate days past due, update arrears balances, and create recovery cases.')]
class AgeLoanArrears extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ArrearsAgingService $aging): int
    {
        $businessDate = CarbonImmutable::parse($this->option('date') ?: today()->toDateString());
        $result = $aging->run($businessDate);
        $this->info("Processed {$result['processed']} loan(s); {$result['inArrears']} in arrears; {$result['casesCreated']} case(s) created.");

        return self::SUCCESS;
    }
}
