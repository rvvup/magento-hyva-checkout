<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Rvvup\Payments\Gateway\Method;

/**
 * Lists the saved Rvvup cards a logged in customer can pay with. Only what the template needs is returned,
 * the gateway token never leaves the server and ownership is always taken from the session, not the browser.
 */
class SavedCardTokens
{
    private const PAYMENT_METHOD_CODE = 'rvvup_CARD';

    /** @var PaymentTokenManagementInterface */
    private $paymentTokenManagement;

    /** @var CustomerSession */
    private $customerSession;

    /** @var SavedCardsAvailability */
    private $savedCardsAvailability;

    /**
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param CustomerSession $customerSession
     * @param SavedCardsAvailability $savedCardsAvailability
     */
    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        CustomerSession $customerSession,
        SavedCardsAvailability $savedCardsAvailability
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->customerSession = $customerSession;
        $this->savedCardsAvailability = $savedCardsAvailability;
    }

    /**
     * @param array $parameters The decoded rvvup_parameters
     * @return array[] Each with publicHash, brand, last4 and expiry
     */
    public function getCards(array $parameters): array
    {
        if (!$this->savedCardsAvailability->isAvailable($parameters)) {
            return [];
        }

        $cards = [];
        $customerId = (int)$this->customerSession->getCustomerId();
        foreach ($this->paymentTokenManagement->getListByCustomerId($customerId) as $token) {
            if (!$this->isUsable($token)) {
                continue;
            }
            $details = json_decode((string)$token->getTokenDetails(), true);
            $details = is_array($details) ? $details : [];
            $cards[] = [
                'publicHash' => (string)$token->getPublicHash(),
                'brand' => (string)($details['type'] ?? ''),
                'last4' => substr((string)($details['maskedCC'] ?? ''), -4),
                'expiry' => (string)($details['expirationDate'] ?? ''),
            ];
        }

        return $cards;
    }

    /**
     * @param PaymentTokenInterface $token
     * @return bool
     */
    private function isUsable(PaymentTokenInterface $token): bool
    {
        if (!$token->getIsActive()
            || !$token->getIsVisible()
            || $token->getPaymentMethodCode() !== self::PAYMENT_METHOD_CODE
            || (string)$token->getGatewayToken() === ''
            || (string)$token->getPublicHash() === ''
        ) {
            return false;
        }

        $expiresAt = $token->getExpiresAt();

        return $expiresAt === null || strtotime($expiresAt . ' UTC') > time();
    }
}
