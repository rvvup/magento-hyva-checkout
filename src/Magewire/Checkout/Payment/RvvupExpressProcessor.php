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
use Rvvup\PaymentsHyvaCheckout\Model\ExpressShippingMethod;
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
        $shippingMethods = $this->mapShippingMethods($result['shippingMethods']);

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
        $total = $quote->getGrandTotal();
        $result = [
            'methodOptions' => [
                'APPLE_PAY' => [
                    'paymentRequest' => [
                        'requiredBillingContactFields' => ['postalAddress', 'name', 'email', 'phone'],
                        // Apple quirk - We need these "shipping" fields to fill the billing email and phone
                        'requiredShippingContactFields' => ['email', 'phone']
                    ],
                ]
            ],
            'total' => [
                'amount' => is_numeric($total) ? number_format((float)$total, 2, '.', '') : $total,
                'currency' => $quote->getQuoteCurrencyCode()
            ],
            'billing' => $this->mapAddress($quote->getBillingAddress())
        ];

        if (!$quote->isVirtual()) {
            $result['methodOptions']['APPLE_PAY']['paymentRequest']['requiredShippingContactFields'] = ['postalAddress', 'name', 'email', 'phone'];
            $result['methodOptions']['APPLE_PAY']['paymentRequest']['shippingType'] = 'shipping';
            $result['methodOptions']['APPLE_PAY']['paymentRequest']['shippingContactEditingMode'] = 'available';

            $quoteShippingAddress = $quote->getShippingAddress();
            $result['shipping'] = $this->mapAddress($quoteShippingAddress);

            // If address is null then shipping methods will appear after the address update
            if ($result['shipping'] !== null) {
                $shippingMethods = $this->mapShippingMethods($this->expressPaymentManager->getAvailableShippingMethods($quote));
                // If methods are empty, need to choose a new address in the express sheet
                if (empty($shippingMethods)) {
                    $result['shipping'] = null;
                } else {
                    $result['shippingMethods'] = $shippingMethods;
                    $selectedMethod = $quoteShippingAddress->getShippingMethod();
                    if (empty($selectedMethod)) {
                        $result['shippingMethods'][0]['selected'] = true;
                    } else {
                        for ($i = 0; $i < count($result['shippingMethods']); $i++) {
                            if ($result['shippingMethods'][$i]['id'] === $selectedMethod) {
                                $result['shippingMethods'][$i]['selected'] = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        $this->quoteData = $result;
    }


    /**
     * @param ExpressShippingMethod[] $shippingMethods
     * @return array
     */
    private function mapShippingMethods(array $shippingMethods): array
    {
        return array_reduce($shippingMethods, function ($carry, $method) {
            $carry[] = [
                'id' => $method->getId(),
                'label' => $method->getLabel(),
                'amount' => ['amount' => $method->getAmount(), 'currency' => $method->getCurrency()],
            ];
            return $carry;
        }, []);
    }

    /**
     * @param Address $quoteAddress
     * @return array[]
     */
    private function mapAddress(Quote\Address $quoteAddress): ?array
    {
        // We ignore country code because it's always pre-selected by magento/hyva.
        // We also ignore region, city, postcode because apple partially sets this, if you cancel the sheet after a
        // address change. We only pre-fill the apple sheet when the user has actively entered the other fields.
        if ((!empty($quoteAddress->getStreet()) && !empty($quoteAddress->getStreet()[0])) ||
            !empty($quoteAddress->getFirstname()) ||
            !empty($quoteAddress->getLastname()) ||
            !empty($quoteAddress->getEmail()) ||
            !empty($quoteAddress->getTelephone())
        ) {
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

        return null;
    }
}
