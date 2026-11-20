<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin;

use Hyva\Checkout\ViewModel\Checkout\Payment\MethodList;
use Rvvup\PaymentsHyvaCheckout\Model\RvvupVaultMethodFilter;

/**
 * Hide Rvvup Vault wrapper methods (rvvup_CARD_vault) from the Hyvä Checkout payment method list.
 */
class HideVaultMethods
{
    /** @var RvvupVaultMethodFilter */
    private $filter;

    /**
     * @param RvvupVaultMethodFilter $filter
     */
    public function __construct(RvvupVaultMethodFilter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * MethodList::getList() returns null when the payment methods of the quote cannot be loaded, leave that as is.
     *
     * @param MethodList $subject
     * @param array|null $result
     * @return array|null
     */
    public function afterGetList(MethodList $subject, ?array $result): ?array
    {
        return $result === null ? null : $this->filter->filter($result);
    }
}
