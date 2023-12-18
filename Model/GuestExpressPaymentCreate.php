<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model;

use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Rvvup\Payments\Hyva\Api\GuestExpressPaymentCreateInterface;
use Rvvup\Payments\Hyva\Api\ExpressPaymentCreateInterface;

class GuestExpressPaymentCreate implements GuestExpressPaymentCreateInterface
{
    /**
     * @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var \Rvvup\Payments\Hyva\Api\ExpressPaymentCreateInterface
     */
    private $expressPaymentCreate;

    /**
     * @param \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param \Rvvup\Payments\Hyva\Api\ExpressPaymentCreateInterface $expressPaymentCreate
     * @return void
     */
    public function __construct(
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        ExpressPaymentCreateInterface $expressPaymentCreate
    ) {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->expressPaymentCreate = $expressPaymentCreate;
    }

    /**
     * @param string $cartId
     * @param string $methodCode
     * @return \Rvvup\Payments\Hyva\Api\Data\PaymentActionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Rvvup\Payments\Hyva\Exception\PaymentValidationException
     */
    public function execute(string $cartId, string $methodCode): array
    {
        return $this->expressPaymentCreate->execute(
            (string) $this->maskedQuoteIdToQuoteId->execute($cartId),
            $methodCode
        );
    }
}
