<?php

use Magento\Framework\App\Bootstrap;
use Magento\Framework\Exception\LocalizedException;

require "app/bootstrap.php";
if ($argc < 3) {
    echo "Usage: php setup-ex-vat-product.php <SKU> <price-excluding-vat>\n";
    exit(1);
}
$sku = $argv[1];
$price = (float) $argv[2];

$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();
try {
    $obj->get("Magento\Framework\App\State")->setAreaCode('adminhtml');
} catch (LocalizedException $e) {
    // already set
}

try {
    // Catalogue prices exclude VAT, storefront displays prices including VAT.
    $config = $obj->get("Magento\Framework\App\Config\Storage\WriterInterface");
    $config->save('tax/calculation/price_includes_tax', 0);
    $config->save('tax/display/type', 2);
    $config->save('tax/defaults/country', 'GB');

    $classRepo = $obj->get("Magento\Tax\Api\TaxClassRepositoryInterface");
    $class = $obj->create("Magento\Tax\Api\Data\TaxClassInterface");
    $class->setClassName('ZRF Ex-VAT')->setClassType('PRODUCT');
    $classId = $classRepo->save($class);

    $rateRepo = $obj->get("Magento\Tax\Api\TaxRateRepositoryInterface");
    $rate = $obj->create("Magento\Tax\Api\Data\TaxRateInterface");
    $rate->setTaxCountryId('GB')->setTaxRegionId(0)->setTaxPostcode('*')
        ->setCode('ZRF-VAT-20')->setRate(20);
    $rate = $rateRepo->save($rate);

    $ruleRepo = $obj->get("Magento\Tax\Api\TaxRuleRepositoryInterface");
    $rule = $obj->create("Magento\Tax\Api\Data\TaxRuleInterface");
    $rule->setCode('ZRF-VAT-20')->setPriority(0)
        ->setCustomerTaxClassIds([3])
        ->setProductTaxClassIds([$classId])
        ->setTaxRateIds([$rate->getId()]);
    $ruleRepo->save($rule);

    $repo = $obj->get("Magento\Catalog\Api\ProductRepositoryInterface");
    $product = $repo->get($sku);
    $product->setPrice($price)->setTaxClassId($classId);
    $repo->save($product);
    echo "✅ {$sku}: {$price} ex-VAT, tax class {$classId}\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
