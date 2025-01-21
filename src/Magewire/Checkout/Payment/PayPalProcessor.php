<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Laminas\Http\Request;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Rvvup\Payments\Gateway\Method;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\Model\UserAgentBuilder;
use Rvvup\Payments\Sdk\Curl;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\PaymentsHyvaCheckout\Service\GetPaymentActions;

class PayPalProcessor extends AbstractProcessor
{
    /** @var Curl */
    private $curl;

    /** @var UserAgentBuilder */
    private $userAgentBuilder;

    /** @var bool */
    public $isExpressPayment = false;

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param Session $checkoutSession
     * @param SdkProxy $sdkProxy
     * @param GetPaymentActions $getPaymentActions
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        Session $checkoutSession,
        SdkProxy $sdkProxy,
        GetPaymentActions $getPaymentActions,
        Curl $curl,
        UserAgentBuilder $userAgentBuilder
    ) {
        parent::__construct($serializer, $assetsModel, $getPaymentActions, $checkoutSession, $sdkProxy);

        $this->curl = $curl;
        $this->userAgentBuilder = $userAgentBuilder;
    }

    public function getMethodCode(): string
    {
        return 'rvvup_PAYPAL';
    }

    public function mount(): void
    {
        parent::mount();

        $quote = $this->checkoutSession->getQuote();

        $this->isExpressPayment = $quote->getPayment() !== null &&
            $quote->getPayment()->getAdditionalInformation(Method::EXPRESS_PAYMENT_KEY) === true;
    }

    /**
     * Called in the express payment flow.
     * @return void
     */
    public function placeOrder(): void
    {
        try {
            $this->loadPaymentActions();

            if (array_key_exists('confirmAuthorization', $this->paymentActions) &&
                $this->paymentActions['confirmAuthorization']['method'] == 'url'
            ) {
                // Authorize here instead of onApprove for express payments as the PayPal order is updated and updates
                // can only be made before the PayPal order is authorized.
                $options = [
                    'headers' => ['Content-Type: application/json'],
                    'json' => [],
                    'user_agent' => $this->userAgentBuilder->get(),
                ];
                $confirmAuthorizationUrl = $this->paymentActions['confirmAuthorization']['value'];
                $response = $this->curl->request(Request::METHOD_POST, $confirmAuthorizationUrl, $options);
                if ($response->response_code !== 200) {
                    throw new \Exception('Something went wrong when authorizing the payment.');
                }
            }

            $redirectUrl = $this->getRedirectUrl();
            $this->dispatchBrowserEvent(
                'rvvup:update:showModal',
                [
                    'redirectUrl' => $redirectUrl,
                ]
            );
        } catch (\Exception $exception) {
            $detail = [
                'text' => $exception->getMessage(),
                'method' => $this->getMethodCode(),
            ];

            $this->dispatchBrowserEvent('order:place:error', $detail);
            $this->dispatchBrowserEvent(sprintf('order:place:%s:error', $detail['method']), $detail);
            $this->dispatchErrorMessage($detail['text']);
        }
    }
}
