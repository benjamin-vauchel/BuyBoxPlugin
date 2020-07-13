<?php

declare(strict_types=1);

namespace Sylius\PayPalPlugin\Controller;

use Doctrine\Persistence\ObjectManager;
use Payum\Core\Payum;
use Payum\Core\Request\Capture;
use SM\Factory\FactoryInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\PayPalPlugin\Manager\PaymentStateManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreatePayPalOrderAction
{
    /** @var Payum */
    private $payum;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var FactoryInterface */
    private $stateMachineFactory;

    /** @var ObjectManager */
    private $paymentManager;

    /** @var PaymentStateManagerInterface */
    private $paymentStateManager;

    public function __construct(
        Payum $payum,
        OrderRepositoryInterface $orderRepository,
        FactoryInterface $stateMachineFactory,
        ObjectManager $paymentManager,
        PaymentStateManagerInterface $paymentStateManager
    ) {
        $this->payum = $payum;
        $this->orderRepository = $orderRepository;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->paymentManager = $paymentManager;
        $this->paymentStateManager = $paymentStateManager;
    }

    public function __invoke(Request $request): Response
    {
        $token = (string) $request->attributes->get('token');

        /** @var OrderInterface $order */
        $order = $this->orderRepository->findOneByTokenValue($token);
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment(PaymentInterface::STATE_NEW);

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();
        /** @var GatewayConfigInterface $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();

        $this
            ->payum
            ->getGateway($gatewayConfig->getGatewayName())
            ->execute(new Capture($payment))
        ;

        $this->paymentStateManager->process($payment);

        return new JsonResponse([
            'orderID' => $payment->getDetails()['paypal_order_id'],
            'status' => $payment->getState(),
        ]);
    }
}
