<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Tax\Helper\Data as TaxHelper;

/**
 * Amount shown to the customer on the product page, as passed to the payment widgets.
 */
class ProductAmount implements ArgumentInterface
{
    /** @var TaxHelper */
    private $taxHelper;

    public function __construct(TaxHelper $taxHelper)
    {
        $this->taxHelper = $taxHelper;
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
            $amount = (float) $this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), true);
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
            return (float) $this->taxHelper->getTaxPrice($product, (float) $amount->getBaseAmount(), true);
        }

        return (float) $amount->getValue();
    }
}
