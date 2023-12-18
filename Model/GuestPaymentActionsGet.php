<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model;

use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Rvvup\Payments\Hyva\Api\GuestPaymentActionsGetInterface;

class GuestPaymentActionsGet implements GuestPaymentActionsGetInterface
{
    /**
     * @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var \Rvvup\Payments\Hyva\Model\PaymentActionsGetInterface
     */
    private $paymentActionsGet;

    /**
     * @param \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param \Rvvup\Payments\Hyva\Model\PaymentActionsGetInterface $paymentActionsGet
     * @return void
     */
    public function __construct(
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        PaymentActionsGetInterface $paymentActionsGet
    ) {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->paymentActionsGet = $paymentActionsGet;
    }

    /**
     * Get the payment actions for the masked cart ID.
     *
     * @param string $cartId
     * @return \Rvvup\Payments\Hyva\Api\Data\PaymentActionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $cartId): array
    {
        return $this->paymentActionsGet->execute((string) $this->maskedQuoteIdToQuoteId->execute($cartId));
    }
}
