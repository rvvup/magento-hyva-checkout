<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Rvvup\Payments\Api\Data\PaymentActionInterface;

class GetPaymentsActionsResult
{
    /** @var PaymentActionInterface|null */
    private $authorization;

    /** @var PaymentActionInterface|null */
    private $cancel;

    /** @var PaymentActionInterface|null */
    private $capture;

    /** @var PaymentActionInterface|null */
    private $confirmAuthorization;

    /**
     * @param PaymentActionInterface|null $authorization
     * @param PaymentActionInterface|null $cancel
     * @param PaymentActionInterface|null $capture
     * @param PaymentActionInterface|null $confirmAuthorization
     */
    public function __construct(
        ?PaymentActionInterface $authorization = null,
        ?PaymentActionInterface $cancel = null,
        ?PaymentActionInterface $capture = null,
        ?PaymentActionInterface $confirmAuthorization = null
    ) {
        $this->authorization = $authorization;
        $this->cancel = $cancel;
        $this->capture = $capture;
        $this->confirmAuthorization = $confirmAuthorization;
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

    public function getConfirmAuthorization(): ?PaymentActionInterface
    {
        return $this->confirmAuthorization;
    }
}
