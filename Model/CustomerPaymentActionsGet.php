<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model;

use Rvvup\Payments\Hyva\Api\CustomerPaymentActionsGetInterface;

class CustomerPaymentActionsGet implements CustomerPaymentActionsGetInterface
{
    /**
     * @var \Rvvup\Payments\Hyva\Model\PaymentActionsGetInterface
     */
    private $paymentActionsGet;

    /**
     * @param \Rvvup\Payments\Hyva\Model\PaymentActionsGetInterface $paymentActionsGet
     * @return void
     */
    public function __construct(PaymentActionsGetInterface $paymentActionsGet)
    {
        $this->paymentActionsGet = $paymentActionsGet;
    }

    /**
     * Get the payment actions for the customer ID & cart ID.
     *
     * @param string $customerId
     * @param string $cartId
     * @return \Rvvup\Payments\Hyva\Api\Data\PaymentActionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(string $customerId, string $cartId): array
    {
        return $this->paymentActionsGet->execute($cartId, $customerId);
    }
}
