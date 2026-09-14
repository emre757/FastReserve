<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Creating = 'creating'; // payment record created but checkout session has not yet been recorded
    case Pending = 'pending'; // checkout is open and waiting for customer
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired'; // checkout expired before payment was confirmed
    case RefundPending = 'refund_pending'; // payment confirmed but must be refunded
    case Refunded = 'refunded';
}
