<?php

namespace Rvvup\PaymentsHyvaCheckout\Plugin;
use \Hyva\Checkout\Model\CustomCondition\IsCustomer as HyvaIsCustomer;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class IsCustomer
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
     * @param HyvaIsCustomer $subject
     * @param bool $result
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterValidate(HyvaIsCustomer $subject, bool $result): bool
    {
        $cart = $this->sessionCheckout->getQuote();
        if (!($cart->getCustomer() && $cart->getCustomer()->getId())) {
            return false;
        }

        return $result;
    }
}
