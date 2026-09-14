<?php

namespace App\Http\Controllers\Payments\Stripe;

use App\Actions\Payments\SyncCompanyPaymentAccountState;
use App\Models\CompanyPaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\StripeClient;
use Stripe\UnhandledNotificationDetails;
use Stripe\V2\Core\EventNotification;

final class StripeAccountWebhookController
{
    private function fallbackCallback(): \Closure
    {
        return function (
            EventNotification $eventNotification,
            StripeClient $client,
            UnhandledNotificationDetails $details,
        ): void {
            logger()->info('Unhandled Stripe event notification', [
                'id' => $eventNotification->id,
                'type' => $eventNotification->type,
                'known_event_type' => $details->isKnownEventType,
            ]);
        };
    }

    public function __invoke(Request $request, StripeClient $stripe, SyncCompanyPaymentAccountState $syncCompanyPaymentAccountState): Response
    {
        $handler = $stripe->notificationHandler(config('services.stripe.webhook_secret'), $this->fallbackCallback());

        $syncStateCallback = function ($event_notification) use ($syncCompanyPaymentAccountState) {
            $paymentAccount = CompanyPaymentAccount::query()
                ->where(
                    'provider_account_id',
                    $event_notification->related_object->id,
                )
                ->firstOrFail();

            $syncCompanyPaymentAccountState->execute($paymentAccount);
        };

        // webhook types
        $handler->onV2CoreAccountIncludingConfigurationRecipientCapabilityStatusUpdated($syncStateCallback);
        $handler->onV2CoreAccountClosed($syncStateCallback);

        $handler->handle(
            $request->getContent(),
            (string) $request->header('Stripe-Signature'),
        );

        return response()->noContent();
    }
}
