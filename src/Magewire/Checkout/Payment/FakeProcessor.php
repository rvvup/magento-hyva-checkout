<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment\AbstractProcessor;

class FakeProcessor extends AbstractProcessor
{
    public function getMethodCode(): string
    {
        return 'rvvup_FAKE_PAYMENT_METHOD';
    }
}
