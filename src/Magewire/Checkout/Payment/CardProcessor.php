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
use Rvvup\PaymentsHyvaCheckout\Service\SavedCardsAvailability;

class CardProcessor extends AbstractProcessor
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh'
    ];

    /** @var PaymentSessionManager */
    private $paymentSessionManager;

    /** @var SavedCardsAvailability */
    private $savedCardsAvailability;

    /** @var array */
    public $paymentSessionResult = [];

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param Session $checkoutSession
     * @param SdkProxy $sdkProxy
     * @param GetPaymentActions $getPaymentActions
     * @param PaymentSessionManager $paymentSessionManager
     * @param SavedCardsAvailability $savedCardsAvailability
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        Session $checkoutSession,
        SdkProxy $sdkProxy,
        GetPaymentActions $getPaymentActions,
        PaymentSessionManager $paymentSessionManager,
        SavedCardsAvailability $savedCardsAvailability
    ) {
        parent::__construct($serializer, $assetsModel, $getPaymentActions, $checkoutSession, $sdkProxy);

        $this->paymentSessionManager = $paymentSessionManager;
        $this->savedCardsAvailability = $savedCardsAvailability;
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
     * @param bool $savePaymentMethod Whether the shopper ticked "Save this card"
     * @param string $publicHash Public hash of the saved card the shopper chose, empty for a new card
     * @return void
     */
    public function createPaymentSession(
        string $checkoutId,
        bool $savePaymentMethod = false,
        string $publicHash = ''
    ): void {
        $quote = $this->checkoutSession->getQuote();
        $this->savedCardsAvailability->applySavedCard($quote, $this->parameters, $publicHash);
        /* The browser only decides what is asked, the server decides what is allowed. */
        $this->savedCardsAvailability->apply($quote, $this->parameters, $savePaymentMethod && $publicHash === '');

        $this->paymentSessionResult = $this->paymentSessionManager->create(
            $quote,
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
