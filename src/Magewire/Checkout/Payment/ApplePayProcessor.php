<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\PaymentsHyvaCheckout\Service\GetPaymentActions;
use Rvvup\PaymentsHyvaCheckout\Service\PaymentSessionManager;

class ApplePayProcessor extends AbstractProcessor
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh'
    ];

    /** @var PaymentSessionManager */
    private $paymentSessionManager;

    /** @var array */
    public $paymentSessionResult;

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param GetPaymentActions $getPaymentActions
     * @param Session $checkoutSession
     * @param SdkProxy $sdkProxy
     * @param PaymentSessionManager $paymentSessionManager
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        GetPaymentActions $getPaymentActions,
        Session $checkoutSession,
        SdkProxy $sdkProxy,
        PaymentSessionManager $paymentSessionManager,
    ) {
        parent::__construct($serializer, $assetsModel, $getPaymentActions, $checkoutSession, $sdkProxy);
        $this->paymentSessionManager = $paymentSessionManager;
    }

    public function getMethodCode(): string
    {
        return 'rvvup_APPLE_PAY';
    }
    public function boot(): void
    {
        parent::boot();

        if ($this->showInline()) {
            $this->switchTemplate('Rvvup_PaymentsHyvaCheckout::component/payment/apple-pay-inline-processor.phtml');
        }
    }

    public function placeOrder(): void
    {
        $this->loadPaymentActions();
        $redirectUrl = $this->getRedirectUrl();
        $this->redirect($redirectUrl);
    }

    /**
     * @return bool
     */
    public function showInline(): bool
    {
        if (isset($this->parameters['settings']['apple_pay']['applePayFlow'])) {
            return $this->parameters['settings']['apple_pay']['applePayFlow'] == 'INLINE';
        }
        return false;
    }

    public function createPaymentSession(string $checkoutId): void
    {
        $this->paymentSessionResult = $this->paymentSessionManager->create($this->checkoutSession->getQuote(), $checkoutId, $this);
    }
}
