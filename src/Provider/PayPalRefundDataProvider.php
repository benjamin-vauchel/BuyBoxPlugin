<?php

declare(strict_types=1);

namespace Sylius\BuyboxPlugin\Provider;

use Sylius\BuyboxPlugin\Api\CacheAuthorizeClientApiInterface;
use Sylius\BuyboxPlugin\Api\GenericApiInterface;
use Sylius\BuyboxPlugin\Exception\PayPalWrongDataException;

final class PayPalRefundDataProvider implements PayPalRefundDataProviderInterface
{
    private CacheAuthorizeClientApiInterface $authorizeClientApi;

    private PayPalPaymentMethodProviderInterface $payPalPaymentMethodProvider;

    private GenericApiInterface $genericApi;

    public function __construct(
        CacheAuthorizeClientApiInterface $authorizeClientApi,
        GenericApiInterface $genericApi,
        PayPalPaymentMethodProviderInterface $payPalPaymentMethodProvider
    ) {
        $this->authorizeClientApi = $authorizeClientApi;
        $this->genericApi = $genericApi;
        $this->payPalPaymentMethodProvider = $payPalPaymentMethodProvider;
    }

    public function provide(string $url): array
    {
        $paymentMethod = $this->payPalPaymentMethodProvider->provide();
        $token = $this->authorizeClientApi->authorize($paymentMethod);

        $refundData = $this->genericApi->get($token, $url);

        /** @var string[] $link */
        foreach ($refundData['links'] as $link) {
            if ($link['rel'] === 'up') {
                return $this->genericApi->get($token, $link['href']);
            }
        }

        throw new PayPalWrongDataException();
    }
}
