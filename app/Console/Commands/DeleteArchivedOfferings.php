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

        $count = Offering::onlyTrashed()->where('deleted_at', '<=', $deleteDate)->forceDelete();

        $this->info("Deleted {$count} archived offerings.");

        return self::SUCCESS;
    }
}
