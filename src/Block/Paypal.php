<?php
declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\CartInterface;
use Hyva\Theme\Service\CurrentTheme;

class Paypal extends Template
{
    /** @var Session */
    private $checkoutSession;

    /** @var CurrentTheme */
    private $theme;

    /**
     * @param Session $checkoutSession
     * @param Template\Context $context
     * @param CurrentTheme $theme
     * @param array $data
     */
    public function __construct(
        Session $checkoutSession,
        Template\Context $context,
        CurrentTheme $theme,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->theme = $theme;
        parent::__construct($context,$data);
    }

    /**
     * @return CartInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(): CartInterface
    {
        return $this->checkoutSession->getQuote();
    }

    public function isHyvaThemeUsed(): bool
    {
        return (bool)$this->theme->isHyva();
    }

}
