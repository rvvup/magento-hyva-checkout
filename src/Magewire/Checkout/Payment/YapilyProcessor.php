<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

class YapilyProcessor extends AbstractProcessor
{
    public function getMethodCode(): string
    {
        return 'rvvup_YAPILY';
    }
}
