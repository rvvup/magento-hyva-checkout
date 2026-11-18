<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data as TaxHelper;

/**
 * Amount shown to the customer on the product page, as passed to the payment widgets.
 */
class ProductAmount implements ArgumentInterface
{
    /** @var TaxHelper */
    private $taxHelper;

    /** @var TaxCalculationInterface */
    private $taxCalculation;

    /** @var QuoteDetailsInterfaceFactory */
    private $quoteDetailsFactory;

    /** @var QuoteDetailsItemInterfaceFactory */
    private $quoteDetailsItemFactory;

    /** @var TaxClassKeyInterfaceFactory */
    private $taxClassKeyFactory;

    /** @var AddressInterfaceFactory */
    private $addressFactory;

    /** @var RegionInterfaceFactory */
    private $regionFactory;

    /** @var GroupRepositoryInterface */
    private $groupRepository;

    /** @var CustomerSession */
    private $customerSession;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var PriceCurrencyInterface */
    private $priceCurrency;

    public function __construct(
        TaxHelper $taxHelper,
        TaxCalculationInterface $taxCalculation,
        QuoteDetailsInterfaceFactory $quoteDetailsFactory,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        AddressInterfaceFactory $addressFactory,
        RegionInterfaceFactory $regionFactory,
        GroupRepositoryInterface $groupRepository,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->taxHelper = $taxHelper;
        $this->taxCalculation = $taxCalculation;
        $this->quoteDetailsFactory = $quoteDetailsFactory;
        $this->quoteDetailsItemFactory = $quoteDetailsItemFactory;
        $this->taxClassKeyFactory = $taxClassKeyFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->groupRepository = $groupRepository;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Price the customer pays, including VAT, formatted with 2 decimals. This does not depend on whether the
     * store displays prices including or excluding tax.
     * For bundle products this is the current total, or the minimal price when nothing is selected.
     *
     * @param ProductInterface|Product $product
     * @return string
     */
    public function getAmount($product): string
    {
        $finalPrice = $product->getPriceInfo()->getPrice('final_price');
        $amount = $this->inclusiveValue($product, $finalPrice->getAmount());

        if ($amount <= 0.0 && $product->getTypeId() === 'bundle') {
            $amount = $this->inclusiveValue($product, $finalPrice->getMinimalPrice());
        }

        if ($amount <= 0.0) {
            // The final price is in the base currency, the amounts of the price info are in the display currency.
            $price = (float) $this->priceCurrency->convert($product->getFinalPrice());
            $amount = $this->priceInclTax($product, $price, $this->taxHelper->priceIncludesTax());
        }

        return number_format($amount, 2, '.', '');
    }

    /**
     * The amount's value follows the store's tax display setting. When the store displays prices excluding tax,
     * add the tax back to the net amount so the widgets always receive the price the customer pays.
     *
     * @param ProductInterface|Product $product
     * @param \Magento\Framework\Pricing\Amount\AmountInterface $amount
     */
    private function inclusiveValue($product, $amount): float
    {
        if ($this->taxHelper->displayPriceExcludingTax()) {
            // Only the tax adjustment is removed, other adjustments such as FPT stay in the amount.
            return $this->priceInclTax($product, (float) $amount->getValue(['tax']), false);
        }

        return (float) $amount->getValue();
    }

    /**
     * Price including tax. This deliberately does not use Catalog\Helper\Data::getTaxPrice(): it skips the
     * calculation when the catalog prices exclude tax and the store displays prices excluding tax (see
     * Tax\Model\Config::needPriceConversion()), which is exactly the case where the tax has to be added.
     * The quote details are built like that helper does, so the customer's tax class, the default tax
     * destination, cross border trade and the configured rounding and calculation algorithm apply.
     *
     * @param ProductInterface|Product $product
     * @param float $price
     * @param bool $priceIncludesTax Whether $price already contains the tax
     */
    private function priceInclTax($product, float $price, bool $priceIncludesTax): float
    {
        if ($price <= 0.0) {
            return $price;
        }

        $item = $this->quoteDetailsItemFactory->create()
            ->setQuantity(1)
            ->setCode($product->getSku())
            ->setType('product')
            ->setUnitPrice($price)
            ->setIsTaxIncluded($priceIncludesTax)
            ->setTaxClassKey($this->taxClassKey((int) $product->getTaxClassId()));

        $quoteDetails = $this->quoteDetailsFactory->create()
            ->setItems([$item])
            ->setCustomerId($this->customerSession->getCustomerId())
            ->setCustomerTaxClassKey($this->taxClassKey($this->customerTaxClassId()))
            ->setBillingAddress($this->taxAddress($this->customerSession->getDefaultTaxBillingAddress()))
            ->setShippingAddress($this->taxAddress($this->customerSession->getDefaultTaxShippingAddress()));

        $items = $this->taxCalculation
            ->calculateTax($quoteDetails, (int) $this->storeManager->getStore()->getId(), true)
            ->getItems();

        return (float) array_shift($items)->getPriceInclTax();
    }

    private function taxClassKey(?int $taxClassId): TaxClassKeyInterface
    {
        return $this->taxClassKeyFactory->create()
            ->setType(TaxClassKeyInterface::TYPE_ID)
            ->setValue($taxClassId);
    }

    /**
     * Same lookup as Catalog\Helper\Data::getTaxPrice(): the tax class of the customer group. The group is kept on
     * pages served from the full page cache, the customer id is not.
     */
    private function customerTaxClassId(): ?int
    {
        $groupId = $this->customerSession->getCustomerGroupId();

        return $groupId === null ? null : (int) $this->groupRepository->getById($groupId)->getTaxClassId();
    }

    /**
     * @param array|null $taxAddress Default tax address of the session (country_id, region_id, postcode)
     */
    private function taxAddress($taxAddress): ?AddressInterface
    {
        if (empty($taxAddress)) {
            return null;
        }

        $address = $this->addressFactory->create()
            ->setCountryId($taxAddress['country_id'])
            ->setPostcode($taxAddress['postcode']);

        if (isset($taxAddress['region_id'])) {
            $address->setRegion($this->regionFactory->create()->setRegionId($taxAddress['region_id']));
        }

        return $address;
    }
}
