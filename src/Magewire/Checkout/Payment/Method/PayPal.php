<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Gateway\Method;
use Rvvup\Payments\Model\CartExpressPaymentRemove;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;

class PayPal extends Component
{
    private SerializerInterface $serializer;
    private Session $checkoutSession;
    private Assets $assetsModel;
    private SdkProxy $sdkProxy;
    private CartExpressPaymentRemove $cartExpressPaymentRemove;

    public array $parameters = [];

    public function __construct(
        SerializerInterface $serializer,
        Session $checkoutSession,
        Assets $assetsModel,
        SdkProxy $sdkProxy,
        CartExpressPaymentRemove $cartExpressPaymentRemove
    ) {
        $this->serializer = $serializer;
        $this->checkoutSession = $checkoutSession;
        $this->assetsModel = $assetsModel;
        $this->sdkProxy = $sdkProxy;
        $this->cartExpressPaymentRemove = $cartExpressPaymentRemove;
    }

    public function mount(): void
    {
        $this->parameters = $this->serializer->unserialize($this->assetsModel->getRvvupParametersJsObject());
    }

    public function getTotal(): string
    {
        return (string)$this->checkoutSession->getQuote()->getGrandTotal();
    }

    public function getPayLaterConfigValue(string $key): mixed
    {
        if (!isset($this->parameters['settings']['paypal']['checkout'])) {
            return false;
        }

        if (in_array($key, ['enabled', 'textSize'])) {
            return $this->parameters['settings']['paypal']['checkout']['payLaterMessaging'][$key];
        }

        return $this->parameters['settings']['paypal']['checkout']['payLaterMessaging'][$key]['value'];
    }

    public function isExpressPayment(): bool
    {
        $payment = $this->checkoutSession->getQuote()->getPayment();

        return $payment->getAdditionalInformation(Method::EXPRESS_PAYMENT_KEY) !== null;
    }

    public function cancel(): void
    {
        $cart = $this->checkoutSession->getQuote();
        $payment = $cart->getPayment();

        $this->cartExpressPaymentRemove->execute((string)$cart->getId());
        if ($payment->getAdditionalInformation(Method::EXPRESS_PAYMENT_KEY)) {
            $rvvupOrderId = $payment->getAdditionalInformation(Method::ORDER_ID);
            $paymentId = $payment->getAdditionalInformation(Method::PAYMENT_ID);

            $this->sdkProxy->cancelPayment($paymentId, $rvvupOrderId);
        }

        $this->emitToRefresh('checkout.payment.methods');
    }
}
