<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;

class Messaging extends Template
{
    /** @var Session */
    private $checkoutSession;

    /**
     * @param Template\Context $context
     * @param Session $session
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $session,
        array $data = []
    ) {
        $this->checkoutSession = $session;
        parent::__construct($context, $data);
    }

    /** Get Rvvup messaging error */
    public function getRvvupError(): ?string
    {
        $error = $this->checkoutSession->getRvvupErrorMessage();
        /** Clear the error so it's shown only once */
        $this->checkoutSession->setRvvupErrorMessage();

        return $error;
    }
}
