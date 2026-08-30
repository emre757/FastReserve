<?php

namespace App\Events\Offerings;

use App\Data\AuditData;
use App\Enums\AuditAction;
use App\Events\Contracts\AuditableEvent;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OfferingCreated implements AuditableEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Offering $offering,
        public User $actor,
        public ?string $ip = null,
    ) {}

    // no new value: unneccesary, when offering is updated/deleted you can show affected changes
    // while checking the value of a created offering is easily done by heading to its show page
    public function auditData(): AuditData
    {
        return new AuditData(
            subject: $this->offering,
            action: AuditAction::OfferingCreated->value,
            teamId: $this->offering->team_id,
            actor: $this->actor,
            metadata: ['ip_address' => $this->ip],
        );
    }
}
