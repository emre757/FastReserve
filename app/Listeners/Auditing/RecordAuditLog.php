<?php

namespace App\Listeners\Auditing;

use App\Events\Contracts\AuditableEvent;
use App\Models\AuditLog;

final class RecordAuditLog
{
    /**
     * Handle the event.
     */
    public function handle(AuditableEvent $event): void
    {
        $data = $event->auditData();

        AuditLog::create([
            'team_id' => $data->teamId,
            'actor_id' => $data->actor?->getKey(),
            'actor_type' => $data->actor?->getMorphClass(),
            'subject_id' => $data->subject->getKey(),
            'subject_type' => $data->subject->getMorphClass(),
            'action' => $data->action,
            'old_values' => $data->old_values,
            'new_values' => $data->new_values,
            'metadata' => $data->metadata,
        ]);
    }
}
