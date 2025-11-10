<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

class ZopaRetailFinanceProcessor extends AbstractProcessor
{
    public function getMethodCode(): string
    {
        return 'rvvup_ZOPA_RETAIL_FINANCE';
    }

    public function placeOrder(): void
    {
        $this->loadPaymentActions();
        $redirectUrl = $this->getRedirectUrl();
        $this->redirect($redirectUrl);
    }
}
