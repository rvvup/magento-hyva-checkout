<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlFactory;
use Rvvup\Payments\Controller\Redirect\In;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\Service\PaymentSessionService;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\PaymentsHyvaCheckout\Service\GetPaymentActions;

class ApplePayProcessor extends AbstractProcessor
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh'
    ];

    /** @var PaymentSessionService */
    private $paymentSessionService;

    /** @var UrlFactory */
    private $urlFactory;

    /** @var array */
    public $paymentSessionResult;

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param GetPaymentActions $getPaymentActions
     * @param Session $checkoutSession
     * @param SdkProxy $sdkProxy
     * @param PaymentSessionService $paymentSessionService
     * @param UrlFactory $urlFactory
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        GetPaymentActions $getPaymentActions,
        Session $checkoutSession,
        SdkProxy $sdkProxy,
        PaymentSessionService $paymentSessionService,
        UrlFactory $urlFactory
    ) {
        parent::__construct($serializer, $assetsModel, $getPaymentActions, $checkoutSession, $sdkProxy);
        $this->paymentSessionService = $paymentSessionService;
        $this->urlFactory = $urlFactory;
    }

    public $hydrated = false;

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
        $quote = $this->checkoutSession->getQuote();

        $paymentSession = $this->paymentSessionService->create($quote, $checkoutId);

        $url = $this->urlFactory->create();
        $url->setQueryParam(In::PARAM_RVVUP_ORDER_ID, $paymentSession["id"]);
        $this->paymentSessionResult = ["paymentSessionId" => $paymentSession["id"], "redirectUrl" => $url->getUrl('rvvup/redirect/in')];
    }
}
