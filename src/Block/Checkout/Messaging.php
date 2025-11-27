<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Rvvup\Payments\Model\SessionMessagesGet;

class Messaging extends Template
{
    /** @var Session */
    private $checkoutSession;

    /** @var SessionMessagesGet */
    private $sessionMessagesGet;

    /**
     * @param Template\Context $context
     * @param Session $session
     * @param SessionMessagesGet $sessionMessagesGet
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $session,
        SessionMessagesGet $sessionMessagesGet,
        array $data = []
    ) {
        $this->checkoutSession = $session;
        $this->sessionMessagesGet = $sessionMessagesGet;
        parent::__construct($context, $data);
    }

    /** Get Rvvup messaging error */
    public function getRvvupError(): ?string
    {
        $messages = $this->sessionMessagesGet->execute();
        if (!empty($messages)) {
            return end($messages)->getText();
        }

        $error = $this->checkoutSession->getRvvupErrorMessage();
        /** Clear the error so it's shown only once */
        $this->checkoutSession->setRvvupErrorMessage();

        return $error;
    }
}
