<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment\Method;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Payment;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Gateway\Method;
use Rvvup\Payments\Model\CartExpressPaymentRemove;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;

class PayPal extends Component
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var Session */
    private $checkoutSession;

    /** @var Assets */
    private $assetsModel;

    /** @var SdkProxy */
    private $sdkProxy;

    /** @var CartExpressPaymentRemove */
    private $cartExpressPaymentRemove;

    /** @var array */
    public $parameters = [];

    /**
     * @param SerializerInterface $serializer
     * @param Session $checkoutSession
     * @param Assets $assetsModel
     * @param SdkProxy $sdkProxy
     * @param CartExpressPaymentRemove $cartExpressPaymentRemove
     */
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

    /**
     * @param string $key
     * @return mixed
     */
    public function getPayLaterConfigValue(string $key)
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

    public function cancel(?Payment $payment = null): void
    {
        $cart = $this->checkoutSession->getQuote();
        if (!$payment) {
            $payment = $cart->getPayment();
        }

        if ($payment->getAdditionalInformation(Method::EXPRESS_PAYMENT_KEY)) {
            $rvvupOrderId = $payment->getAdditionalInformation(Method::ORDER_ID) ?:
                $payment->getAdditionalInformation(Method::TRANSACTION_ID);
            $paymentId = $payment->getAdditionalInformation(Method::PAYMENT_ID);

            $this->sdkProxy->cancelPayment($paymentId, $rvvupOrderId);
        }
        $this->cartExpressPaymentRemove->execute((string)$cart->getId());

        $this->emitToRefresh('checkout.payment.methods');
    }
}
