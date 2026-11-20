<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;
use Rvvup\Payments\Gateway\Method;

class SavedCardsAvailability
{
    /** @var CustomerSession */
    private $customerSession;

    /**
     * @param CustomerSession $customerSession
     */
    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * Saving a card needs the merchant to have enabled it and a logged in customer to own the token.
     *
     * @param array $parameters The decoded rvvup_parameters
     * @return bool
     */
    public function isAvailable(array $parameters): bool
    {
        return !empty($parameters['settings']['card']['savedCardsEnabled'])
            && $this->customerSession->isLoggedIn();
    }

    /**
     * Records the shopper's choice on the quote payment, where the Rvvup payment session service reads it.
     * Anything the shopper cannot or did not choose is cleared, so an earlier opt in on the same quote
     * (an abandoned attempt, or a since logged out session) never leaks into this payment.
     *
     * @param Quote $quote
     * @param array $parameters The decoded rvvup_parameters
     * @param bool $requested
     * @return void
     */
    public function apply(Quote $quote, array $parameters, bool $requested): void
    {
        $payment = $quote->getPayment();

        if ($requested && $this->isAvailable($parameters)) {
            $payment->setAdditionalInformation(Method::SAVE_PAYMENT_METHOD, true);
            return;
        }

        $payment->unsAdditionalInformation(Method::SAVE_PAYMENT_METHOD);
    }
}
