<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Product\View\Info;

use Hyva\Checkout\Model\Session as HyvaCheckoutSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Api\ExpressPaymentCreateInterface;
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

    /**
     * @param Session $checkoutSession
     * @param CartInterfaceFactory $cartFactory
     * @param CartRepositoryInterface $cartRepository
     * @param ProductRepositoryInterface $productRepository
     * @param BillingAddressManagementInterface $billingAddressManagement
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
        $this->addressFactory = $addressFactory;
        $this->hyvaCheckoutSession = $hyvaCheckoutSession;
        $this->expressPaymentCreate = $expressPaymentCreate;
        $this->payPal = $payPal;
    }

    public function createExpressPayment(string $method, string $addToCartRequest): void
    {
        parse_str($addToCartRequest, $request);
        $product = $this->productRepository->getById($request['product']);

        $cart = $this->getCart();
        $cart->addProduct($product, new DataObject($request));
        $this->cartRepository->save($cart);

        $cart->collectTotals();

        $paymentActions = $this->expressPaymentCreate->execute(
            (string)$cart->getEntityId(),
            $method
        );

        $this->authorizationToken = $this->getAuthorizationToken($paymentActions);
    }

    public function saveAddress(array $billingAddressInput): void
    {
        $cart = $this->checkoutSession->getQuote();

        $billingAddress = $this->addressFactory->create();
        $billingAddress->setData($billingAddressInput);

        if (!$cart->getCustomerEmail()) {
            $cart->setCustomerEmail($billingAddress->getEmail());
        }

        $this->billingAddressManagement->assign($cart->getId(), $billingAddress, true);

        if ($this->hyvaCheckoutSession->getSteps()) {
            $this->hyvaCheckoutSession->restart();
        }

        $this->redirect('checkout');
    }

    /** Cancel Express Paypal Payment */
    public function cancelExpressPayment(): void
    {
        $cart = $this->checkoutSession->getQuote();
        $payment = $cart->getPayment();
        $payment->setAdditionalInformation(Method::EXPRESS_PAYMENT_KEY, true);

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

    /** Creates new cart */
    private function getCart(): CartInterface
    {
        $cart = $this->cartFactory->create();
        $cart->setStoreId($this->checkoutSession->getStoreId());
        $this->cartRepository->save($cart);

        $this->checkoutSession->replaceQuote($cart);

        return $cart;
    }
}
