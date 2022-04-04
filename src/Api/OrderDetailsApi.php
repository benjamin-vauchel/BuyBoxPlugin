<?php

declare(strict_types=1);

namespace Sylius\BuyboxPlugin\Api;

use Sylius\BuyboxPlugin\Client\PayPalClientInterface;

final class OrderDetailsApi implements OrderDetailsApiInterface
{
    private PayPalClientInterface $client;

    public function __construct(PayPalClientInterface $client)
    {
        $this->client = $client;
    }

    public function get(string $token, string $orderId): array
    {
        return $this->client->get(sprintf('v2/checkout/orders/%s', $orderId), $token);
    }
}
