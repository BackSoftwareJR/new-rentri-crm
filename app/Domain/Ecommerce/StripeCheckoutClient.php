<?php

namespace App\Domain\Ecommerce;

use App\Domain\Ecommerce\Contracts\StripeCheckoutClientInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeCheckoutClient implements StripeCheckoutClientInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return object{id: string, url: string|null}
     */
    public function createCheckoutSession(array $params): object
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));

        /** @var Session $session */
        $session = Session::create($params);

        return (object) [
            'id'  => $session->id,
            'url' => $session->url,
        ];
    }
}
