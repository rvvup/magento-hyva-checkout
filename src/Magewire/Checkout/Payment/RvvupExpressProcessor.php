<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Service\Express\ExpressPaymentManager;
use Rvvup\Payments\Service\Express\ExpressPaymentRequestMapper;
use Rvvup\Payments\Service\Shipping\ShippingMethodService;
use Rvvup\PaymentsHyvaCheckout\Service\PaymentSessionManager;

class RvvupExpressProcessor extends Component
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',

        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh',

        'shipping_address_saved' => 'refresh',
        'shipping_address_activated' => 'refresh',

        'billing_address_saved' => 'refresh',
        'billing_address_activated' => 'refresh',
    ];

    /** @var Session */
    private $checkoutSession;

    /** @var ExpressPaymentManager */
    private $expressPaymentManager;


    /** @var ShippingMethodService */
    private $shippingMethodService;

    /** @var PaymentSessionManager */
    private $paymentSessionManager;

    /** @var ExpressPaymentRequestMapper */
    private $expressPaymentRequestMapper;

    /** @var array */
    public $paymentSessionResult;

    /** @var array */
    public $shippingAddressChangeResult = [];

    /** @var array */
    public $quoteData = [];

    /**
     * @param Session $checkoutSession
     * @param PaymentSessionManager $paymentSessionManager
     * @param ExpressPaymentManager $expressPaymentManager
     * @param ShippingMethodService $shippingMethodService
     * @param ExpressPaymentRequestMapper $expressPaymentRequestMapper
     */
    public function __construct(
        Session               $checkoutSession,
        PaymentSessionManager $paymentSessionManager,
        ExpressPaymentManager $expressPaymentManager,
        ShippingMethodService       $shippingMethodService,
        ExpressPaymentRequestMapper $expressPaymentRequestMapper
    )
    {
        $this->paymentSessionManager = $paymentSessionManager;
        $this->checkoutSession = $checkoutSession;
        $this->expressPaymentManager = $expressPaymentManager;
        $this->shippingMethodService = $shippingMethodService;
        $this->expressPaymentRequestMapper = $expressPaymentRequestMapper;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function mount()
    {
        $quote = $this->checkoutSession->getQuote();
        $this->setQuoteData($quote);
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function booted()
    {
        $quote = $this->checkoutSession->getQuote();
        $this->setQuoteData($quote);
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function shippingAddressChanged(array $address): void
    {
        $error = null;
        if (empty($address['countryCode'])) {
            $error = [
                'code' => 'addressUnserviceable',
                'message' => 'Invalid shipping country'
            ];
        }

        $result = $this->expressPaymentManager->updateShippingAddress($this->checkoutSession->getQuote(), $address);

        $this->setQuoteData($result['quote']);
        $shippingMethods = $this->expressPaymentRequestMapper->mapShippingMethods($result['shippingMethods']);

        if (!empty($shippingMethods)) {
            $shippingMethods[0]['selected'] = true;
        } else {
            $error = [
                'code' => 'addressUnserviceable',
                'message' => 'No shipping methods available'
            ];
        }

        $this->shippingAddressChangeResult = [
            'total' => $this->quoteData['total'],
            'shippingMethods' => $shippingMethods,
            'error' => $error
        ];
    }

    /**
     * @param string $methodId
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function shippingMethodChanged(string $methodId): void
    {
        $quote = $this->shippingMethodService->updateShippingMethod($this->checkoutSession->getQuote(), $methodId);
        $this->setQuoteData($quote);
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function createPaymentSession(string $checkoutId, array $data): void
    {
        $quote = $this->expressPaymentManager->updateQuoteBeforePaymentAuth($this->checkoutSession->getQuote(), $data);
        $this->setQuoteData($quote);
        $this->paymentSessionResult = $this->paymentSessionManager->create($quote, $checkoutId, $this);
    }

    /**
     * @param Quote $quote
     * @return void
     */
    private function setQuoteData(Quote $quote): void
    {
        $this->quoteData = $this->expressPaymentRequestMapper->map($quote);
    }
}
