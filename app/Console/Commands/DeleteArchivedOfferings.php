<?php

namespace App\Console\Commands;

use App\Models\Offering;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:delete-archived-offerings')]
#[Description('Delete all soft deleted offerings that are 30+ days old.')]
class DeleteArchivedOfferings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleteDate = now()->subDays(30);

        $count = 0;

        // process models in batches to follow model events rule
        // no reason to bypass this rule as this application has no huge number of records
        foreach (
            Offering::onlyTrashed()
                ->where('deleted_at', '<=', $deleteDate)
                ->lazyById(500) as $offering
        ) {
            if ($offering->forceDelete()) {
                $count++;
            }
        }

        $this->info("Deleted {$count} archived offerings.");

        return self::SUCCESS;
    }
}
