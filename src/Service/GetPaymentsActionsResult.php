<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Rvvup\Payments\Api\Data\PaymentActionInterface;

class GetPaymentsActionsResult
{
    private ?PaymentActionInterface $authorization;
    private ?PaymentActionInterface $cancel;
    private ?PaymentActionInterface $capture;

    public function __construct(
        PaymentActionInterface $authorization = null,
        PaymentActionInterface $cancel = null,
        PaymentActionInterface $capture = null,
    ) {
        $this->authorization = $authorization;
        $this->cancel = $cancel;
        $this->capture = $capture;
    }

    public function getAuthorization(): ?PaymentActionInterface
    {
        return $this->authorization;
    }

    public function getAuthorizationMethod(): ?string
    {
        return $this->authorization->getMethod();
    }

    public function getCancel(): ?PaymentActionInterface
    {
        return $this->cancel;
    }

    public function getCancelUrl(): ?string
    {
        if ($this->cancel === null || $this->cancel->getMethod() != 'redirect_url') {
            return null;
        }

        return $this->cancel->getValue();
    }

    public function getCapture(): ?PaymentActionInterface
    {
        return $this->capture;
    }

    public function getCaptureUrl(): ?string
    {
        if ($this->capture === null || $this->capture->getMethod() != 'redirect_url') {
            return null;
        }

        return $this->capture->getValue();
    }
}
