<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Ecommerce\EcommercePaymentGatewayService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class StripeEcommerceWebhookController extends Controller
{
    public function __invoke(Request $request, EcommercePaymentGatewayService $gateway): Response
    {
        try {
            $gateway->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (RuntimeException $e) {
            app(\App\Support\Logging\StructuredLogService::class)->warning(
                'stripe',
                'webhook_rejected',
                'Webhook Stripe rifiutato',
                [
                    'outcome' => 'failure',
                    'context' => [
                        'error' => $e->getMessage(),
                    ],
                ],
            );

            return response($e->getMessage(), 400);
        }

        return response('ok', 200);
    }
}
