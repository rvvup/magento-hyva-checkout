<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

class KlarnaProcessor extends AbstractProcessor
{
    public function getMethodCode(): string
    {
        return 'rvvup_KLARNA';
    }

    public function placeOrder(): void
    {
        $this->loadPaymentActions();
        $redirectUrl = $this->getRedirectUrl();
        $this->redirect($redirectUrl);
    }
}
