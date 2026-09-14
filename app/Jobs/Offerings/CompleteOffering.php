<?php

namespace App\Jobs\Offerings;

use App\Enums\OfferingStatus;
use App\Models\Offering;
use App\Support\Database\LockContext;
use App\Support\Database\OrderedTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CompleteOffering implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $offeringId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(OrderedTransaction $transactions): void
    {
        $transactions->run(function (LockContext $locks): void {
            $offering = $locks->findOrFail(Offering::class, $this->offeringId);

            if (
                $offering->status === OfferingStatus::Active
                && $offering->ends_at !== null
                && $offering->ends_at <= now()
            ) {
                $offering->update(['status' => OfferingStatus::Completed]);
            }
        });
    }
}
