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
     * Price the customer pays (tax-aware per store display settings), formatted with 2 decimals.
     * For bundle products this is the current total, or the minimal price when nothing is selected.
     *
     * @param ProductInterface|Product $product
     * @return string
     */
    public function getAmount($product): string
    {
        $priceInfo = $product->getPriceInfo();
        $finalPrice = $priceInfo->getPrice('final_price');
        $amount = (float) $finalPrice->getAmount()->getValue();

        if ($amount <= 0.0 && $product->getTypeId() === 'bundle') {
            $amount = (float) $finalPrice->getMinimalPrice()->getValue();
        }

        if ($amount <= 0.0) {
            $amount = (float) $this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), true);
        }

        return number_format($amount, 2, '.', '');
    }
}
