<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Rvvup\Api\Model\PaymentType;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\PaymentsHyvaCheckout\Service\GetPaymentActions;
use Rvvup\PaymentsHyvaCheckout\Service\PaymentSessionManager;

class CardProcessor extends AbstractProcessor
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh'
    ];

    /** @var PaymentSessionManager */
    private $paymentSessionManager;

    /** @var array */
    public $paymentSessionResult = [];

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param Session $checkoutSession
     * @param SdkProxy $sdkProxy
     * @param GetPaymentActions $getPaymentActions
     * @param PaymentSessionManager $paymentSessionManager
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        Session $checkoutSession,
        SdkProxy $sdkProxy,
        GetPaymentActions $getPaymentActions,
        PaymentSessionManager $paymentSessionManager
    ) {
        parent::__construct($serializer, $assetsModel, $getPaymentActions, $checkoutSession, $sdkProxy);

        $this->paymentSessionManager = $paymentSessionManager;
    }

    public function boot(): void
    {
        parent::boot();

        if (!$this->showForm()) {
            $this->switchTemplate('Rvvup_PaymentsHyvaCheckout::component/payment/card-modal-processor.phtml');
        }
    }

    public function getMethodCode(): string
    {
        return 'rvvup_CARD';
    }

    /**
     * Creates the Rvvup payment session for the inline card flow. Called from the SDK its
     * beforePaymentAuth event, so a session only exists once the card details have been validated.
     *
     * @param string $checkoutId
     * @return void
     */
    public function createPaymentSession(string $checkoutId): void
    {
        $this->paymentSessionResult = $this->paymentSessionManager->create(
            $this->checkoutSession->getQuote(),
            $checkoutId,
            $this,
            PaymentType::STANDARD
        );
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
