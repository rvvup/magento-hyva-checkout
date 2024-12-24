<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Session;
use Rvvup\PaymentsHyvaCheckout\Model\ExpressShippingMethod;

class ExpressPaymentManager
{

    /** @var ShipmentEstimationInterface */
    private $shipmentEstimation;


    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var ShippingInformationInterfaceFactory */
    private $shippingInformationFactory;

    /** @var ShippingInformationManagementInterface */
    private $shippingInformationManagement;

    /** @var Session */
    private $customerSession;

    public function __construct(
        ShipmentEstimationInterface $shipmentEstimation,
        CartRepositoryInterface     $quoteRepository,
        ShippingInformationInterfaceFactory $shippingInformationFactory,
        ShippingInformationManagementInterface $shippingInformationManagement,
        Session $customerSession
    )
    {
        $this->shipmentEstimation = $shipmentEstimation;
        $this->quoteRepository = $quoteRepository;
        $this->shippingInformationFactory = $shippingInformationFactory;
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->customerSession = $customerSession;
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
            ->setCountryId($address['countryCode'])
            ->setCity($address['city'] ?? null)
            ->setRegion($address['state'] ?? null)
//            ->setRegionId() Set it by looking up state and getting the id
            ->setPostcode($address['postcode'] ?? null)
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
     * @param string $methodId
     * @return Quote
     */
    public function updateShippingMethod(Quote $quote, string $methodId): Quote
    {
        $codes = explode('_', $methodId);
        if (count($codes) !== 2) {
            return $quote;
        }
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setShippingMethod($methodId)->setCollectShippingRates(true)->collectShippingRates();

        $this->shippingInformationManagement->saveAddressInformation(
            $quote->getId(),
            $this->shippingInformationFactory->create()
                ->setShippingAddress($shippingAddress)
                ->setShippingCarrierCode($codes[0])
                ->setShippingMethodCode($codes[1])
        );

        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();

        $this->quoteRepository->save($quote);
        return $quote;
    }

    public function updateQuoteBeforePaymentAuth(Quote $quote, array $data): Quote
    {
        if (!$quote->isVirtual() &&
            isset($data['shipping']['address']) &&
            isset($data['shipping']['address']['postcode']) && // Only missing if shipping was not used in express sheet
            isset($data['shipping']['contact'])
        ) {
            $this->setUpdatedAddressDetails(
                $quote->getShippingAddress(),
                $data['shipping']['contact'],
                $data['shipping']['address']
            );
        }

        if (isset($data['billing']['address']) && isset($data['billing']['contact'])) {
            $contact = $data['billing']['contact'];
            $this->setUpdatedAddressDetails($quote->getbillingAddress(), $contact, $data['billing']['address']);

            $quote->setCustomerFirstname($contact['givenName'] ?? null)
                ->setCustomerLastname($contact['surname'] ?? null);

            if ($this->customerSession->isLoggedIn()) {
                $quote->setCheckoutMethod(Onepage::METHOD_CUSTOMER)
                    ->setCustomerId($this->customerSession->getCustomerId());
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST)
                    ->setCustomerId(0)
                    ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID)
                    ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true);
            }
        }

        if (isset($data['paymentMethod'])) {
            $quote->getPayment()->setMethod('rvvup_' . $data['paymentMethod']);
        }

        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();

        $this->quoteRepository->save($quote);
        return $quote;
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

    /**
     * @param Quote\Address $quoteAddress
     * @param $contact
     * @param $address
     * @return void
     */
    public function setUpdatedAddressDetails(Quote\Address $quoteAddress, $contact, $address): void
    {
        $quoteAddress
            ->setFirstname($contact['givenName'] ?? null)
            ->setLastname($contact['surname'] ?? null)
            ->setEmail($contact['email'] ?? null)
            ->setTelephone($contact['phoneNumber'] ?? null)
            ->setStreet($address['addressLines'] ?? null)
            ->setCountryId($address['countryCode'] ?? null)
            ->setRegion($address['state'] ?? null)
//            ->setRegionId() Set it by looking up state and getting the id
            ->setPostcode($address['postcode'] ?? null)
            ->setCity($address['city'] ?? null);
    }

}
