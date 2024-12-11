<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment;

class ApplePayProcessor extends AbstractProcessor
{
    protected $listeners = [
    'shipping_method_selected' => 'refresh',
    'coupon_code_applied' => 'refresh',
    'coupon_code_revoked' => 'refresh'
];
    public $hydrated = false;

    public function getMethodCode(): string
    {
        return 'rvvup_APPLE_PAY';
    }
    public function boot(): void
    {
        parent::boot();

        if ($this->showInline()) {
            $this->switchTemplate('Rvvup_PaymentsHyvaCheckout::component/payment/apple-pay-inline-processor.phtml');
        }
    }

    public function updated($value, $name): void
    {
        parent::updated($value, $name);
        if ($this->showInline()) {
            $this->switchTemplate('Rvvup_PaymentsHyvaCheckout::component/payment/apple-pay-inline-processor.phtml');
        }
        var_dump($name);
    }

    public function placeOrder(): void
    {
        $this->loadPaymentActions();
        $redirectUrl = $this->getRedirectUrl();
        $this->redirect($redirectUrl);
    }

    /**
     * @return bool
     */
    public function showInline(): bool
    {
        if (isset($this->parameters['settings']['apple_pay']['applePayFlow'])) {
            return $this->parameters['settings']['apple_pay']['applePayFlow'] == 'INLINE';
        }
        return false;
    }
}
