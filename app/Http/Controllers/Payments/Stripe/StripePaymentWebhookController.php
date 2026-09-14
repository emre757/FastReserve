<?php

namespace App\Http\Controllers\Payments\Stripe;

use App\Actions\Payments\CompleteReservationPayment;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final readonly class StripePaymentWebhookController
{
    private function handleCompleted(Session $session): void
    {
        if ($session->payment_status === 'paid') {
            $this->handleSucceeded($session);
        }

        // TODO: for payments that were completed but haven't been confirmed need special handling
    }

    private function handleSucceeded(Session $session): void
    {
        $this->completeReservationPayment->execute($session->id, PaymentStatus::Paid, $session->payment_intent);
    }

    private function handleFailed(Session $session): void
    {
        $this->completeReservationPayment->execute($session->id, PaymentStatus::Failed, $session->payment_intent);
    }

    private function handleExpired(Session $session): void
    {
        $this->completeReservationPayment->execute($session->id, PaymentStatus::Expired, $session->payment_intent);
    }

    public function __construct(
        private CompleteReservationPayment $completeReservationPayment,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.payment_webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            return response('Invalid webhook.', 400);
        }

        $session = $event->data->object;

        if (! $session instanceof Session) {
            return response('Invalid Checkout Session.', 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCompleted(
                $session,
            ),

            'checkout.session.async_payment_succeeded' => $this->handleSucceeded(
                $session,
            ),

            'checkout.session.async_payment_failed' => $this->handleFailed(
                $session,
            ),

            'checkout.session.expired' => $this->handleExpired(
                $session,
            ),

            default => null,
        };

        return response()->noContent();
    }
}
