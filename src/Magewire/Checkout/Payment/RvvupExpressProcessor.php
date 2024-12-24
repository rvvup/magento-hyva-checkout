<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magewirephp\Magewire\Component;
use Rvvup\PaymentsHyvaCheckout\Service\ExpressPaymentManager;
use Rvvup\PaymentsHyvaCheckout\Service\PaymentSessionManager;

class RvvupExpressProcessor extends Component
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh',

        'shipping_address_added' => 'refresh',
        'guest_shipping_address_added' => 'refresh',
        'customer_shipping_address_added' => 'refresh',

        'shipping_address_submitted' => 'refresh',
        'guest_shipping_address_submitted' => 'refresh',
        'customer_shipping_address_submitted' => 'refresh',

        'shipping_address_saved' => 'refresh',
        'guest_shipping_address_saved' => 'refresh',
        'customer_shipping_address_saved' => 'refresh',

        'shipping_address_activated' => 'refresh',

        'billing_address_added' => 'refresh',
        'guest_billing_address_added' => 'refresh',
        'customer_billing_address_added' => 'refresh',

        'billing_address_submitted' => 'refresh',
        'guest_billing_address_submitted' => 'refresh',
        'customer_billing_address_submitted' => 'refresh',

        'billing_address_saved' => 'refresh',
        'guest_billing_address_saved' => 'refresh',
        'customer_billing_address_saved' => 'refresh',

        'billing_address_activated' => 'refresh',
    ];

    /** @var Session */
    private $checkoutSession;

    /** @var ExpressPaymentManager */
    private $expressPaymentManager;

    /** @var PaymentSessionManager */
    private $paymentSessionManager;

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
    public function boot()
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
        if (empty($address['countryCode'])) {
            $detail = ['text' => 'Invalid shipping country'];
            $this->dispatchBrowserEvent('order:place:error', $detail);
            $this->dispatchErrorMessage($detail['text']);
        }

        $result = $this->expressPaymentManager->updateShippingAddress($this->checkoutSession->getQuote(), $address);

        $this->setQuoteData($result['quote']);
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
            'total' => $this->quoteData['total'],
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
        $result = [
            'methodOptions' => [
                'APPLE_PAY' => [
                    'paymentRequest' => [
                        'requiredBillingContactFields' => ['postalAddress', 'name', 'email', 'phone'],
                        // Apple quirk - We need these "shipping" fields to fill the billing email and phone
                        'requiredShippingContactFields' => ['email', 'phone']
                    ],
                ]
            ]
        ];

        $total = $quote->getGrandTotal();
        $result['total'] = [
            'amount' => is_numeric($total) ? number_format((float)$total, 2, '.', '') : $total,
            'currency' => $quote->getQuoteCurrencyCode()
        ];

        $result['billing'] = $this->mapAddress($quote->getBillingAddress());

        if (!$quote->isVirtual()) {
            $result['shipping'] = $this->mapAddress($quote->getShippingAddress());
            if ($this->isCompleteAddress($result['shipping'])) {
                $result['shipping'] = null; // We already have a complete address so don't need to ask for it in the express sheet
            } else {
                $result['methodOptions']['APPLE_PAY']['paymentRequest']['requiredShippingContactFields'] = ['postalAddress', 'name', 'email', 'phone'];
                $result['methodOptions']['APPLE_PAY']['paymentRequest']['shippingType'] = 'shipping';
                $result['methodOptions']['APPLE_PAY']['paymentRequest']['shippingContactEditingMode'] = 'available';
            }
        }

        $this->quoteData = $result;
    }

    /**
     * @param Address $quoteAddress
     * @return array[]
     */
    private function mapAddress(Quote\Address $quoteAddress): array
    {
        return [
            'address' => [
                'addressLines' => $quoteAddress->getStreet(),
                'city' => $quoteAddress->getCity(),
                'countryCode' => $quoteAddress->getCountryId(),
                'postcode' => $quoteAddress->getPostcode(),
                'state' => $quoteAddress->getRegion()
            ],
            'contact' => [
                'givenName' => $quoteAddress->getFirstname(),
                'surname' => $quoteAddress->getLastname(),
                'email' => $quoteAddress->getEmail(),
                'phoneNumber' => $quoteAddress->getTelephone()
            ]
        ];
    }

    /**
     * @param array $data
     * @return bool
     */
    private function isCompleteAddress(array $data): bool
    {
        return !empty($data['address']['addressLines'])
            && !empty($data['address']['city'])
            && !empty($data['address']['countryCode'])
            && !empty($data['address']['postcode'])
            && !empty($data['contact']['email'])
            && !empty($data['contact']['phoneNumber']);
    }
}
