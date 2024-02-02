<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Rvvup\Payments\Gateway\Method;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\PaymentsHyvaCheckout\Service\GetPaymentActions;

class PayPalProcessor extends AbstractProcessor
{
    public bool $isExpressPayment = false;

    public function getMethodCode(): string
    {
        return 'rvvup_PAYPAL';
    }

    public function mount(): void
    {
        parent::mount();

        $quote = $this->checkoutSession->getQuote();

        $this->isExpressPayment = $quote->getPayment() !== null &&
            $quote->getPayment()->getAdditionalInformation(Method::EXPRESS_PAYMENT_KEY) === true;
    }
}
