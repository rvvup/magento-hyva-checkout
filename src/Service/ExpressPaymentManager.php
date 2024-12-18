<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\Quote;
use Rvvup\PaymentsHyvaCheckout\Model\ExpressShippingMethod;

class ExpressPaymentManager
{

    /** @var ShipmentEstimationInterface */
    private $shipmentEstimation;


    /** @var CartRepositoryInterface */
    private $quoteRepository;

    public function __construct(
        ShipmentEstimationInterface $shipmentEstimation,
        CartRepositoryInterface     $quoteRepository
    )
    {
        $this->shipmentEstimation = $shipmentEstimation;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param Quote $quote
     * @param array $address
     * @return array $result
     */
    public function updateShippingAddress(Quote $quote, array $address): array
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress
            ->setCountryId($address['country'])
            ->setCity($address['city'] ?? null)
            ->setRegion($address['state'] ?? null)
//            ->setRegionId() Set it by looking up state and getting the id
            ->setPostcode($address['postal_code'] ?? null)
            ->setCollectShippingRates(true);

        $shippingMethods = $this->getAvailableShippingMethods($quote);
        if (empty($shippingMethods)) {
            $shippingAddress->setShippingMethod('');
        } else {
            $shippingAddress->setShippingMethod($shippingMethods[0]->getId());
        }
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();

        $this->quoteRepository->save($quote);
        return ['quote' => $quote, 'shippingMethods' => $shippingMethods];
    }

    /**
     * @param Quote $quote
     * @return ExpressShippingMethod[] $shippingMethods
     */
    private function getAvailableShippingMethods(Quote $quote): array
    {
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($quote->getId(), $quote->getShippingAddress());
        if (empty($shippingMethods)) {
            return [];
        }
        $returnedShippingMethods = [];
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->getErrorMessage()) {
                continue;
            }

            $returnedShippingMethods[] = new ExpressShippingMethod($shippingMethod, $quote->getQuoteCurrencyCode());
        }
        return $returnedShippingMethods;
    }

}
