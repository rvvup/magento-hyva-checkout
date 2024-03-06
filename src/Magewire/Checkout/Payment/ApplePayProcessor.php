<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

class ApplePayProcessor extends AbstractProcessor
{
    public function getMethodCode(): string
    {
        return 'rvvup_APPLE_PAY';
    }

    public function placeOrder(): void
    {
        $redirectUrl = null;
        if (array_key_exists('capture', $this->paymentActions)) {
            $redirectUrl = $this->paymentActions['capture']['value'];
        }

        if (array_key_exists('authorization', $this->paymentActions) &&
            $this->paymentActions['authorization']['method'] == 'redirect_url'
        ) {
            $redirectUrl = $this->paymentActions['authorization']['value'];
        }

        $this->redirect($redirectUrl);
    }
}
