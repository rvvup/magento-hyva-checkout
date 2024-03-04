<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Rvvup\Payments\Gateway\Method;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\PaymentsHyvaCheckout\Service\GetPaymentActions;

class CardProcessor extends AbstractProcessor
{
    /** @var CartRepositoryInterface */
    private $cartRepository;

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param Session $checkoutSession
     * @param SdkProxy $sdkProxy
     * @param GetPaymentActions $getPaymentActions
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        Session $checkoutSession,
        SdkProxy $sdkProxy,
        GetPaymentActions $getPaymentActions,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($serializer, $assetsModel, $getPaymentActions, $checkoutSession, $sdkProxy);

        $this->cartRepository = $cartRepository;
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

    public function handleCallback(?string $authorizationResponse, ?string $threeDSecureResponse): void
    {
        if ($authorizationResponse === null) {
            $authorizationResponse = false;
        }

        $cart = $this->checkoutSession->getQuote();
        $payment = $cart->getPayment();

        $payment->setAdditionalInformation('authorization_response', $authorizationResponse);
        $payment->setAdditionalInformation('three_d_secure_response', $threeDSecureResponse);

        $this->cartRepository->save($cart);
    }

    public function placeOrder(): void
    {
        if (!$this->showForm()) {
            parent::placeOrder();
            return;
        }

        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();

        $authorizationResponse = $payment->getAdditionalInformation('authorization_response');
        $threeDSecureResponse = $payment->getAdditionalInformation('three_d_secure_response');

        $rvvupOrderId = (string)$payment->getAdditionalInformation('transaction_id');
        $rvvupPaymentId = $payment->getAdditionalInformation(Method::PAYMENT_ID);

        $this->sdkProxy->confirmCardAuthorization(
            $rvvupPaymentId,
            $rvvupOrderId,
            $authorizationResponse,
            $threeDSecureResponse
        );

        parent::placeOrder();
    }

    private function showForm(): bool
    {
        return $this->parameters['settings']['card']['flow'] == 'INLINE';
    }
}
