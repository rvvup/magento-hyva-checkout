<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magewirephp\Magewire\Component;
use Psr\Log\LoggerInterface;
use Rvvup\Api\Model\ApplicationSource;
use Rvvup\Api\Model\CheckoutCreateInput;
use Rvvup\Api\Model\PaymentType;
use Rvvup\Payments\Service\ApiProvider;
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

    /** @var ApiProvider */
    private $apiProvider;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    public $paymentSessionResult;

    /** @var string|null Token returned when checkout is created on demand (PDP flow) */
    public $checkoutToken = null;

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
     * @param ApiProvider $apiProvider
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Session                     $checkoutSession,
        PaymentSessionManager       $paymentSessionManager,
        ExpressPaymentManager       $expressPaymentManager,
        ShippingMethodService       $shippingMethodService,
        ExpressPaymentRequestMapper $expressPaymentRequestMapper,
        ApiProvider                 $apiProvider,
        StoreManagerInterface       $storeManager,
        LoggerInterface             $logger
    ) {
        $this->paymentSessionManager = $paymentSessionManager;
        $this->checkoutSession = $checkoutSession;
        $this->expressPaymentManager = $expressPaymentManager;
        $this->shippingMethodService = $shippingMethodService;
        $this->expressPaymentRequestMapper = $expressPaymentRequestMapper;
        $this->apiProvider = $apiProvider;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
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
    public function createPaymentSession(?string $checkoutId, array $data): void
    {
        if (!$checkoutId) {
            $checkout = $this->createCheckoutOnDemand();
            $checkoutId = $checkout['id'];
            $this->checkoutToken = $checkout['token'];
        }

        $quote = $this->expressPaymentManager->updateQuoteBeforePaymentAuth($this->checkoutSession->getQuote(), $data);
        $this->setQuoteData($quote);
        $this->paymentSessionResult = $this->paymentSessionManager->create($quote, $checkoutId, $this, PaymentType::EXPRESS);
    }

    /**
     * Create a Rvvup checkout on demand for PDP express checkout flow.
     *
     * @return array{id: string, token: string}
     * @throws LocalizedException
     */
    private function createCheckoutOnDemand(): array
    {
        try {
            $storeId = (string) $this->storeManager->getStore()->getId();
            $checkoutInput = (new CheckoutCreateInput())->setSource(ApplicationSource::MAGENTO_CHECKOUT);

            try {
                $checkoutInput->setMetadata([
                    "domain" => $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB, true)
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Ignoring error getting base url: ' . $e->getMessage());
            }

            $result = $this->apiProvider->getSdk($storeId)->checkouts()->create($checkoutInput, null);

            if (!$result->getId() || !$result->getToken()) {
                throw new LocalizedException(__('Failed to create Rvvup checkout'));
            }

            return [
                'id' => $result->getId(),
                'token' => $result->getToken(),
            ];
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Error creating on-demand checkout: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to create Rvvup checkout'));
        }
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
