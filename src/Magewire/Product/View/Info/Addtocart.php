<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Product\View\Info;

use Hyva\Checkout\Exception\CheckoutException;
use Hyva\Checkout\Model\Session as HyvaCheckoutSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Model\ShippingAddressManagementInterface;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Api\ExpressPaymentCreateInterface;
use Rvvup\Payments\Exception\PaymentValidationException;
use Rvvup\Payments\Gateway\Method;
use Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment\Method\PayPal;

class Addtocart extends Component
{
    /** @var Session */
    private $checkoutSession;

    /** @var CartInterfaceFactory */
    private $cartFactory;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var BillingAddressManagementInterface */
    private $billingAddressManagement;

    /** @var AddressInterfaceFactory */
    private $addressFactory;

    /** @var HyvaCheckoutSession */
    private $hyvaCheckoutSession;

    /** @var ExpressPaymentCreateInterface */
    private $expressPaymentCreate;

    /** @var string */
    public $authorizationToken = '';

    /** @var PayPal  */
    private $payPal;

    /** @var ShippingAddressManagementInterface */
    private $shippingAddressManagement;

    /**
     * @param Session $checkoutSession
     * @param CartInterfaceFactory $cartFactory
     * @param CartRepositoryInterface $cartRepository
     * @param ProductRepositoryInterface $productRepository
     * @param BillingAddressManagementInterface $billingAddressManagement
     * @param ShippingAddressManagementInterface $shippingAddressManagement
     * @param AddressInterfaceFactory $addressFactory
     * @param HyvaCheckoutSession $hyvaCheckoutSession
     * @param ExpressPaymentCreateInterface $expressPaymentCreate
     * @param PayPal $payPal
     */
    public function __construct(
        Session $checkoutSession,
        CartInterfaceFactory $cartFactory,
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
        BillingAddressManagementInterface $billingAddressManagement,
        ShippingAddressManagementInterface $shippingAddressManagement,
        AddressInterfaceFactory $addressFactory,
        HyvaCheckoutSession $hyvaCheckoutSession,
        ExpressPaymentCreateInterface $expressPaymentCreate,
        PayPal $payPal
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartFactory = $cartFactory;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->addressFactory = $addressFactory;
        $this->hyvaCheckoutSession = $hyvaCheckoutSession;
        $this->expressPaymentCreate = $expressPaymentCreate;
        $this->payPal = $payPal;
    }

    /**
     * @param string $method
     * @param string|null $addToCartRequest
     * @param bool $isCart
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws PaymentValidationException
     */
    public function createExpressPayment(
        string $method,
        ?string $addToCartRequest = null,
        bool $isCart = false
    ): void
    {
        $cart = $this->checkoutSession->getQuote();
        if (!$isCart) {
            $cart = $cart->removeAllItems();
            $message = $this->addProductToCart($addToCartRequest, $cart);
            if ($message) {
                $this->dispatchErrorMessage($message);
                return;
            }
        }

        $paymentActions = $this->expressPaymentCreate->execute(
            (string)$cart->getEntityId(),
            $method
        );

        $this->authorizationToken = $this->getAuthorizationToken($paymentActions);
    }

    /**
     * @param array $billingAddressInput
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CheckoutException
     * @throws InputException
     */
    public function saveBillingAddress(array $billingAddressInput): void
    {
        $cart = $this->checkoutSession->getQuote();

        $billingAddress = $this->addressFactory->create();
        $billingAddress->setData($billingAddressInput);

        if (!$cart->getCustomerEmail()) {
            $cart->setCustomerEmail($billingAddress->getEmail());
        }

        $this->billingAddressManagement->assign($cart->getId(), $billingAddress);

        if ($this->hyvaCheckoutSession->getSteps()) {
            $this->hyvaCheckoutSession->restart();
        }

        $this->redirect('checkout');
    }

    /**
     * @param array $shippingAddressInput
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CheckoutException
     * @throws InputException
     */
    public function saveShippingAddress(array $shippingAddressInput): void
    {
        $cart = $this->checkoutSession->getQuote();

        $shippingAddress = $this->addressFactory->create();
        $shippingAddress->setData($shippingAddressInput);

        if (!$cart->getCustomerEmail()) {
            $cart->setCustomerEmail($shippingAddress->getEmail());
        }

        if(!$cart->isVirtual()) {
            $this->shippingAddressManagement->assign($cart->getId(), $shippingAddress);
        }
    }

    /** Cancel Express Paypal Payment */
    public function cancelExpressPayment(bool $isCart = false): void
    {
        $cart = $this->checkoutSession->getQuote();
        $payment = $cart->getPayment();
        if (!$isCart) {
            $cart->removeAllItems();
            $this->cartRepository->save($cart);
        }
        $payment->setAdditionalInformation(Method::EXPRESS_PAYMENT_KEY, false);
        $this->payPal->cancel($payment);
    }

    private function getAuthorizationToken(array $paymentActions): string
    {
        foreach ($paymentActions as $paymentAction) {
            if ($paymentAction->getType() === 'authorization') {
                return $paymentAction->getValue();
            }
        }

        throw new \Exception('No authorization token found');
    }

    /**
     * @param string $addToCartRequest
     * @param CartInterface $cart
     * @return string|null
     * @throws NoSuchEntityException
     */
    private function addProductToCart(string $addToCartRequest, CartInterface $cart): ?string
    {
        parse_str($addToCartRequest, $request);
        $product = $this->productRepository->getById($request['product']);
        try {
            $cart->addProduct($product, new DataObject($request));
            $this->cartRepository->save($cart);
            $cart->collectTotals();
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
