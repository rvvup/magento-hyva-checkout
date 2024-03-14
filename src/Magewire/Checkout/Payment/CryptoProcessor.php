<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

class CryptoProcessor extends AbstractProcessor
{
    public function getMethodCode(): string
    {
        return 'rvvup_CRYPTO';
    }
}
