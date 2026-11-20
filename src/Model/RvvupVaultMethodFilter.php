<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Model;

/**
 * Removes Rvvup Vault wrapper methods (e.g. rvvup_CARD_vault) from a list of payment methods.
 *
 * These exist only to drive the Luma UI. Hyvä Checkout has no Vault support, so they must not be listed.
 * Only Rvvup codes are matched; Vault methods from other modules (e.g. braintree_cc_vault) are untouched.
 */
class RvvupVaultMethodFilter
{
    private const RVVUP_VAULT_CODE_PATTERN = '/^rvvup_.+_vault$/';

    /**
     * @param string $code
     * @return bool
     */
    public function isRvvupVaultMethod(string $code): bool
    {
        return (bool)preg_match(self::RVVUP_VAULT_CODE_PATTERN, $code);
    }

    /**
     * @param array $methods Objects exposing getCode() (PaymentMethodInterface)
     * @return array
     */
    public function filter(array $methods): array
    {
        $filtered = [];

        foreach ($methods as $key => $method) {
            if ($this->isRvvupVaultMethod((string)$method->getCode())) {
                continue;
            }
            $filtered[$key] = $method;
        }

        // Keep the original shape: re-index only when the input was a list.
        return array_keys($methods) === range(0, count($methods) - 1) ? array_values($filtered) : $filtered;
    }
}
