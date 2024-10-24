<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

class GooglePayProcessor extends AbstractProcessor
{
    public function getMethodCode(): string
    {
        return 'rvvup_GOOGLE_PAY';
    }
}
