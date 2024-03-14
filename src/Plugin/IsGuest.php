<?php

namespace Rvvup\PaymentsHyvaCheckout\Plugin;

use Hyva\Checkout\Model\CustomCondition\IsGuest as HyvaIsGuest;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class IsGuest
{
    /** @var SessionCheckout */
    private $sessionCheckout;

    /**
     * @param SessionCheckout $sessionCheckout
     */
    public function __construct(
        SessionCheckout $sessionCheckout
    ) {
        $this->sessionCheckout = $sessionCheckout;
    }

    /**
     * @param HyvaIsGuest $subject
     * @param bool $result
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterValidate(HyvaIsGuest $subject, bool $result): bool
    {
        $cart = $this->sessionCheckout->getQuote();
        if (!$cart->getCustomer() || !$cart->getCustomer()->getId()) {
            return true;
        }

        return $result;
    }
}
