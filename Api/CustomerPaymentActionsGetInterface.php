<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Api;

interface CustomerPaymentActionsGetInterface
{
    /**
     * Get the payment actions for the customer ID & cart ID.
     *
     * @param string $customerId
     * @param string $cartId
     * @return \Rvvup\Payments\Hyva\Api\Data\PaymentActionInterface[]
     */
    public function execute(string $customerId, string $cartId): array;
}
