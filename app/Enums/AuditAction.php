<?php

namespace App\Enums;

enum AuditAction: string
{
    case OfferingCreated = 'offering.created';
    case OfferingUpdated = 'offering.updated';
    case TeamDeleted = 'team.deleted';
    case InvitationAccepted = 'invitation.accepted';
}
