<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
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

    /**
     * Records which saved card the shopper pays with. Only the public hash is taken from the browser, the
     * Rvvup payment session service resolves it for the customer on the quote and rejects anything else.
     * An empty hash clears an earlier choice, so a shopper who switches back to a new card is not charged
     * to the saved one. Paying with a saved card never saves a card.
     *
     * @param Quote $quote
     * @param array $parameters The decoded rvvup_parameters
     * @param string $publicHash
     * @return void
     * @throws LocalizedException When a saved card is asked for but saved cards are not available
     */
    public function applySavedCard(Quote $quote, array $parameters, string $publicHash): void
    {
        $payment = $quote->getPayment();

        if ($publicHash === '') {
            $payment->unsAdditionalInformation(Method::PUBLIC_HASH);
            return;
        }

        if (!$this->isAvailable($parameters)) {
            $payment->unsAdditionalInformation(Method::PUBLIC_HASH);
            throw new LocalizedException(__('This saved card can no longer be used. Please use a different card.'));
        }

        $payment->setAdditionalInformation(Method::PUBLIC_HASH, $publicHash);
        $payment->unsAdditionalInformation(Method::SAVE_PAYMENT_METHOD);
    }
}
