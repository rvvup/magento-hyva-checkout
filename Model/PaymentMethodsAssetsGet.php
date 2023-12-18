<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model;

use Rvvup\Payments\Hyva\Api\PaymentMethodsAssetsGetInterface;
use Rvvup\Payments\Hyva\Api\PaymentMethodsSettingsGetInterface;

class PaymentMethodsAssetsGet implements PaymentMethodsAssetsGetInterface
{
    /**
     * @var \Rvvup\Payments\Hyva\Api\PaymentMethodsSettingsGetInterface
     */
    private $paymentMethodsSettingsGet;

    /**
     * @param \Rvvup\Payments\Hyva\Api\PaymentMethodsSettingsGetInterface $paymentMethodsSettingsGet
     * @return void
     */
    public function __construct(PaymentMethodsSettingsGetInterface $paymentMethodsSettingsGet)
    {
        $this->paymentMethodsSettingsGet = $paymentMethodsSettingsGet;
    }

    /**
     * Get the assets for all payment methods available for the value & currency.
     *
     * @param string $value
     * @param string $currency
     * @param array|string[] $methodCodes // Leave empty for all.
     * @return array
     */
    public function execute(string $value, string $currency, array $methodCodes = []): array
    {
        return array_map(static function ($methodSettings) {
            return $methodSettings['assets'] ?? [];
        }, $this->paymentMethodsSettingsGet->execute($value, $currency, $methodCodes));
    }
}
