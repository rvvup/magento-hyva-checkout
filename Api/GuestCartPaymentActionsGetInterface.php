<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Api;

interface GuestCartPaymentActionsGetInterface
{
    /**
     * Get the payment actions for the cart ID.
     *
     * @param string $cartId
     * @param bool $expressActions
     * @return \Rvvup\Payments\Hyva\Api\Data\PaymentActionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $cartId, bool $expressActions = false): array;
}
