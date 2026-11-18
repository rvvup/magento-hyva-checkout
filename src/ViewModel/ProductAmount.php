<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
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

    /** @var CustomerSession */
    private $customerSession;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        TaxHelper $taxHelper,
        TaxCalculationInterface $taxCalculation,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager
    ) {
        $this->taxHelper = $taxHelper;
        $this->taxCalculation = $taxCalculation;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
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
            // The catalog price already contains the tax when the store enters prices including tax.
            $price = (float) $product->getFinalPrice();
            $amount = $this->taxHelper->priceIncludesTax() ? $price : $this->addTax($product, $price);
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
            // The base amount is the net price, whether the catalog prices include tax or not.
            return $this->addTax($product, (float) $amount->getBaseAmount());
        }

        return (float) $amount->getValue();
    }

    /**
     * Adds the tax to a net price. This deliberately does not use Catalog\Helper\Data::getTaxPrice(): it skips the
     * conversion when the catalog prices exclude tax and the store displays prices excluding tax (see
     * Tax\Model\Config::needPriceConversion()), which is exactly the case where the tax has to be added.
     *
     * @param ProductInterface|Product $product
     * @param float $netPrice
     */
    private function addTax($product, float $netPrice): float
    {
        if ($netPrice <= 0.0) {
            return $netPrice;
        }

        $rate = (float) $this->taxCalculation->getCalculatedRate(
            (int) $product->getTaxClassId(),
            $this->customerSession->getCustomerId() ?: null,
            (string) $this->storeManager->getStore()->getId()
        );

        return round($netPrice * (1 + $rate / 100), 2);
    }
}
