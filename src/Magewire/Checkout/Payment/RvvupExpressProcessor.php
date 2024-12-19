<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlFactory;
use Magewirephp\Magewire\Component;
use Rvvup\ApiException;
use Rvvup\Payments\Controller\Redirect\In;
use Rvvup\Payments\Service\PaymentSessionService;
use Rvvup\PaymentsHyvaCheckout\Service\ExpressPaymentManager;

class RvvupExpressProcessor extends Component
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
    /** @var Session */
    private $checkoutSession;

    /** @var ExpressPaymentManager */
    private $expressPaymentManager;

    /** @var array */
    public $paymentSessionResult;

    /** @var string */
    public $quoteCurrency = 'GBP';

    /** @var string */
    public $quoteAmount = '0';

    /** @var array */
    public $shippingAddressChangeResult = [];

    /**
     * @param Session $checkoutSession
     * @param PaymentSessionService $paymentSessionService
     * @param UrlFactory $urlFactory
     * @param ExpressPaymentManager $expressPaymentManager
     */
    public function __construct(
        Session               $checkoutSession,
        PaymentSessionService $paymentSessionService,
        UrlFactory            $urlFactory,
        ExpressPaymentManager $expressPaymentManager
    )
    {
        $this->paymentSessionService = $paymentSessionService;
        $this->urlFactory = $urlFactory;
        $this->checkoutSession = $checkoutSession;
        $this->expressPaymentManager = $expressPaymentManager;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function boot(){
        $quote = $this->checkoutSession->getQuote();
        $this->setQuoteTotal($quote);
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function shippingAddressChanged(array $address): void
    {
        if (empty($address['countryCode'])) {
            $detail = [
                'text' => "Invalid shipping country",
            ];
            $this->dispatchBrowserEvent('order:place:error', $detail);
            $this->dispatchErrorMessage($detail['text']);
        }

        $result = $this->expressPaymentManager->updateShippingAddress($this->checkoutSession->getQuote(), $address);

        $this->setQuoteTotal($result['quote']);
        $this->shippingAddressChangeResult = [
            'total' => ['amount' => $this->quoteAmount, 'currency' => $this->quoteCurrency],
            'shippingMethods' => array_reduce($result['shippingMethods'], function ($carry, $method) {
                $carry[] = [
                    'id' => $method->getId(),
                    'label' => $method->getLabel(),
                    'amount' => ['amount' => $method->getAmount(), 'currency' => $method->getCurrency()],
                ];
                return $carry;
            }, []),
            'errorMessage' => null,
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
        $quote = $this->expressPaymentManager->updateShippingMethod($this->checkoutSession->getQuote(), $methodId);
        $this->setQuoteTotal($quote);
    }

    /**
     * @throws NoSuchEntityException
     * @throws ApiException
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function createPaymentSession(string $checkoutId): void
    {
        $quote = $this->checkoutSession->getQuote();

        $paymentSession = $this->paymentSessionService->create($quote, $checkoutId);

        $url = $this->urlFactory->create();
        $url->setQueryParam(In::PARAM_RVVUP_ORDER_ID, $paymentSession["id"]);
        $this->paymentSessionResult = ["paymentSessionId" => $paymentSession["id"], "redirectUrl" => $url->getUrl('rvvup/redirect/in')];
    }

    private function setQuoteTotal($quote)
    {
        $total = $quote->getGrandTotal();
        $this->quoteAmount = is_numeric($total) ? number_format((float)$total, 2, '.', '') : $total;
        $this->quoteCurrency = $quote->getQuoteCurrencyCode();
    }
}
