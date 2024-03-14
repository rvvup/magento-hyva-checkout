<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
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

        $data = [$rvvupPaymentId, $rvvupOrderId, $authorizationResponse, $threeDSecureResponse];
        $message = $this->confirmCardAuthorization($data);

        $redirectUrl = $this->getRedirectUrl();

        if (!$message) {
            $this->dispatchBrowserEvent(
                'rvvup:update:showModal',
                ['redirectUrl' => $redirectUrl]
            );
        } else {
            $this->sdkProxy->cancelPayment($rvvupPaymentId, $rvvupOrderId);
            $this->checkoutSession->setRvvupErrorMessage($message);
            $redirectUrl = $this->url->getCurrentUrl();
            $this->redirect($redirectUrl);
        }
    }

    public function getErrorMessage(): ?string
    {
        return $this->checkoutSession->getRvvupErrorMessage();
    }

    /**
     * @param array $data
     * @param int $retries
     * @return string|null
     */
    private function confirmCardAuthorization(array $data, int $retries = 5): ?string
    {
        try {
            list($rvvupPaymentId, $rvvupOrderId, $authorizationResponse, $threeDSecureResponse) = $data;

            $this->sdkProxy->confirmCardAuthorization(
                $rvvupPaymentId,
                $rvvupOrderId,
                $authorizationResponse,
                $threeDSecureResponse
            );
            return null;
        } catch (\Exception $exception) {
            if ($exception instanceof ApiError) {
                 if ($exception->getErrorCode() == 'card_authorization_not_found') {
                    if ($retries > 0) {
                        $retries--;
                        sleep(1);
                        $this->confirmCardAuthorization($data, $retries);
                    } else {
                        $this->logger->error(
                            'Rvvup hyva card inline processor failed, payment id: '.
                            $rvvupPaymentId . ' order id :'. $rvvupOrderId .
                            ' message:' . $exception->getMessage()
                        );
                    }
                }
            }
            return $exception->getMessage();
        }
    }

    public function showForm(): bool
    {
        return $this->parameters['settings']['card']['flow'] == 'INLINE';
    }
}
