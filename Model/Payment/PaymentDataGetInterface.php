<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model\Payment;

interface PaymentDataGetInterface
{
    /**
     * Get the Rvvup payment data from the API by Rvvup order ID.
     *
     * @param string $rvvupId
     * @return array
     */
    public function execute(string $rvvupId): array;
}
