<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\PaymentsHyvaCheckout\Service\GetPaymentActions;

abstract class AbstractProcessor extends Component
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var Assets */
    private $assetsModel;

    /** @var GetPaymentActions */
    private $getPaymentActions;

    /** @var Session */
    protected $checkoutSession;

    /** @var SdkProxy */
    protected $sdkProxy;

    /** @var array */
    public $paymentActions = [];

    /** @var array */
    public $parameters = [];

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param GetPaymentActions $getPaymentActions
     * @param Session $checkoutSession
     * @param SdkProxy $sdkProxy
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        GetPaymentActions $getPaymentActions,
        Session $checkoutSession,
        SdkProxy $sdkProxy
    ) {
        $this->serializer = $serializer;
        $this->assetsModel = $assetsModel;
        $this->getPaymentActions = $getPaymentActions;
        $this->checkoutSession = $checkoutSession;
        $this->sdkProxy = $sdkProxy;
    }

    public function mount(): void
    {
        $this->parameters = $this->serializer->unserialize($this->assetsModel->getRvvupParametersJsObject());
    }

    abstract function getMethodCode(): string;

    public function getInitializationToken(): ?string
    {
        return $this->parameters['settings']['card']['initializationToken'] ?? null;
    }

    public function getLiveStatus(): int
    {
        return $this->parameters['settings']['card']['liveStatus']  ?? 0;
    }

    public function getTranslation(string $type, string $key, string $default): string
    {
        return $this->parameters['settings']['card']['form']['translation'][$type][$key] ?? $default;
    }

    public function loadPaymentActions(): void
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getPayment()->getMethod() != $this->getMethodCode()) {
            return;
        }

        $result = $this->getPaymentActions->execute((int)$quote->getId());

        $this->paymentActions = [
            'authorization' => [
                'method' => $result->getAuthorization()->getMethod(),
                'type' => $result->getAuthorization()->getType(),
                'value' => $result->getAuthorization()->getValue(),
            ],
            'cancel' => [
                'method' => $result->getCancel()->getMethod(),
                'type' => $result->getCancel()->getType(),
                'value' => $result->getCancel()->getValue(),
            ],
        ];

        if ($capture = $result->getCapture()) {
            $this->paymentActions['capture'] = [
                'method' => $capture->getMethod(),
                'type' => $capture->getType(),
                'value' => $capture->getValue(),
            ];
        }
    }

    public function placeOrder(): void
    {
        $this->loadPaymentActions();
        $redirectUrl = $this->getRedirectUrl();

        $this->dispatchBrowserEvent(
            'rvvup:update:showModal',
            [
                'redirectUrl' => $redirectUrl,
            ]
        );
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        $redirectUrl = null;
        if (array_key_exists('capture', $this->paymentActions)) {
            $redirectUrl = $this->paymentActions['capture']['value'];
        }

        if (array_key_exists('authorization', $this->paymentActions) &&
            $this->paymentActions['authorization']['method'] == 'redirect_url'
        ) {
            $redirectUrl = $this->paymentActions['authorization']['value'];
        }
        return $redirectUrl;
    }
}
