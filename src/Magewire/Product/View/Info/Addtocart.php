<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Product\View\Info;

use Hyva\Checkout\Model\Session as HyvaCheckoutSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Api\ExpressPaymentCreateInterface;

class Addtocart extends Component
{
    private Session $checkoutSession;
    private CartInterfaceFactory $cartFactory;
    private CartRepositoryInterface $cartRepository;
    private ProductRepositoryInterface $productRepository;
    private BillingAddressManagementInterface $billingAddressManagement;
    private AddressInterfaceFactory $addressFactory;
    private HyvaCheckoutSession $hyvaCheckoutSession;
    private ExpressPaymentCreateInterface $expressPaymentCreate;

    public string $authorizationToken = '';

    public function __construct(
        Session $checkoutSession,
        CartInterfaceFactory $cartFactory,
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
        BillingAddressManagementInterface $billingAddressManagement,
        AddressInterfaceFactory $addressFactory,
        HyvaCheckoutSession $hyvaCheckoutSession,
        ExpressPaymentCreateInterface $expressPaymentCreate
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartFactory = $cartFactory;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->addressFactory = $addressFactory;
        $this->hyvaCheckoutSession = $hyvaCheckoutSession;
        $this->expressPaymentCreate = $expressPaymentCreate;
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

        $cart->setCustomerEmail($billingAddress->getEmail());

        $this->billingAddressManagement->assign($cart->getId(), $billingAddress, true);

        if ($this->hyvaCheckoutSession->getSteps()) {
            $this->hyvaCheckoutSession->restart();
        }

        $this->redirect('checkout');
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

    private function getCart(): CartInterface
    {
        $cart = $this->cartFactory->create();
        $cart->setStoreId($this->checkoutSession->getStoreId());
        $this->cartRepository->save($cart);

        $this->checkoutSession->replaceQuote($cart);

        return $cart;
    }
}
