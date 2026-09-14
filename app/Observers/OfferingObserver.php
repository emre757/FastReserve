<?php

namespace App\Observers;

use App\Jobs\Offerings\CompleteOffering;
use App\Models\Offering;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/** TODO: handle possibility of job dispatch failure in which case either a new job has to be dispatched
or something else has to be done to make sure offering marks as completed at the end date */
final class OfferingObserver implements ShouldHandleEventsAfterCommit
{
    private function dispatchEndJob(Offering $offering): void
    {
        if ($offering->ends_at !== null) {
            CompleteOffering::dispatch($offering->id)->delay($offering->ends_at);
        }
    }

    /**
     * Handle the Offering "created" event.
     */
    public function created(Offering $offering): void
    {
        $this->dispatchEndJob($offering);
    }

    /**
     * Handle the Offering "updated" event.
     */
    public function updated(Offering $offering): void
    {
        if ($offering->wasChanged('ends_at')) {
            $this->dispatchEndJob($offering);
        }
    }

    /**
     * Handle the Offering "deleted" event.
     */
    public function deleted(Offering $offering): void
    {
        //
    }

    /**
     * Handle the Offering "restored" event.
     */
    public function restored(Offering $offering): void
    {
        //
    }

    /**
     * Handle the Offering "force deleted" event.
     */
    public function forceDeleted(Offering $offering): void
    {
        //
    }
}
