<?php

namespace App\Events\Contracts;

use App\Data\AuditData;

interface AuditableEvent
{
    public function auditData(): AuditData;
}
