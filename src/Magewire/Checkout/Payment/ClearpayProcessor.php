<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

class ClearpayProcessor extends AbstractProcessor
{
    public function getMethodCode(): string
    {
        return 'rvvup_CLEARPAY';
    }
}
