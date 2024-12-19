<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magewirephp\Magewire\Component;
use Rvvup\PaymentsHyvaCheckout\Service\ExpressPaymentManager;
use Rvvup\PaymentsHyvaCheckout\Service\PaymentSessionManager;

class RvvupExpressProcessor extends Component
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh'
    ];

    /** @var Session */
    private $checkoutSession;

    /** @var ExpressPaymentManager */
    private $expressPaymentManager;

    /** @var PaymentSessionManager */
    private $paymentSessionManager;

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
     * @param PaymentSessionManager $paymentSessionManager
     * @param ExpressPaymentManager $expressPaymentManager
     */
    public function __construct(
        Session               $checkoutSession,
        PaymentSessionManager $paymentSessionManager,
        ExpressPaymentManager $expressPaymentManager
    )
    {
        $this->paymentSessionManager = $paymentSessionManager;
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
        $shippingMethods = array_reduce($result['shippingMethods'], function ($carry, $method) {
            $carry[] = [
                'id' => $method->getId(),
                'label' => $method->getLabel(),
                'amount' => ['amount' => $method->getAmount(), 'currency' => $method->getCurrency()],
            ];
            return $carry;
        }, []);
        if (!empty($shippingMethods)) {
            $shippingMethods[0]['selected'] = true;
        }
        $this->shippingAddressChangeResult = [
            'total' => ['amount' => $this->quoteAmount, 'currency' => $this->quoteCurrency],
            'shippingMethods' => $shippingMethods,
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
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function createPaymentSession(string $checkoutId, array $data): void
    {
        $quote = $this->expressPaymentManager->updateQuoteBeforeAuth($this->checkoutSession->getQuote(), $data);
        $this->setQuoteTotal($quote);
        $this->paymentSessionResult = $this->paymentSessionManager->create($quote, $checkoutId, $this);
    }

    private function setQuoteTotal($quote)
    {
        $total = $quote->getGrandTotal();
        $this->quoteAmount = is_numeric($total) ? number_format((float)$total, 2, '.', '') : $total;
        $this->quoteCurrency = $quote->getQuoteCurrencyCode();
    }
}
