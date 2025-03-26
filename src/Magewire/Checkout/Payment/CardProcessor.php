<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Rvvup\PaymentsHyvaCheckout\Service\GetPaymentActions;
use Rvvup\Payments\Gateway\Method;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\Sdk\Exceptions\ApiError;

class CardProcessor extends AbstractProcessor
{
    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var UrlInterface */
    private $url;

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param Session $checkoutSession
     * @param SdkProxy $sdkProxy
     * @param GetPaymentActions $getPaymentActions
     * @param CartRepositoryInterface $cartRepository
     * @param UrlInterface $url
     * @param LoggerInterface $logger
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        Session $checkoutSession,
        SdkProxy $sdkProxy,
        GetPaymentActions $getPaymentActions,
        CartRepositoryInterface $cartRepository,
        UrlInterface $url,
        LoggerInterface $logger
    ) {
        parent::__construct($serializer, $assetsModel, $getPaymentActions, $checkoutSession, $sdkProxy);

        $this->cartRepository = $cartRepository;
        $this->url = $url;
        $this->logger = $logger;
    }

    public function mount(): void
    {
        parent::mount();

        if (!$this->showForm()) {
            $this->switchTemplate('Rvvup_PaymentsHyvaCheckout::component/payment/card-modal-processor.phtml');
        }
    }

    public function getMethodCode(): string
    {
        return 'rvvup_CARD';
    }

    public function placeOrder(): void
    {
        if (!$this->showForm()) {
            parent::placeOrder();
        }
    }

    /**
     * @return bool
     */
    public function showForm(): bool
    {
        if (isset($this->parameters['settings']['card']['flow'])) {
            return $this->parameters['settings']['card']['flow'] == 'INLINE';
        }
        return false;
    }
}
