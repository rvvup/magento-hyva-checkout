<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin\Express;

use Magento\Quote\Model\Quote\Address;
use Rvvup\Payments\Service\Express\ExpressPaymentManager;

class FallbackTelephone
{
    private const PLACEHOLDER_TELEPHONE = '000000000';

    public function aroundSetUpdatedAddressDetails(
        ExpressPaymentManager $subject,
        callable $proceed,
        Address $quoteAddress,
        array $contact,
        array $address
    ): void {
        if (($contact['phoneNumber'] ?? '') === '') {
            $contact['phoneNumber'] = self::PLACEHOLDER_TELEPHONE;
        }

        $proceed($quoteAddress, $contact, $address);
    }
}
