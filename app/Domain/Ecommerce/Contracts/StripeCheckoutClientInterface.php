<?php

namespace App\Domain\Ecommerce\Contracts;

interface StripeCheckoutClientInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return object{id: string, url: string|null}
     */
    public function createCheckoutSession(array $params): object;
}
