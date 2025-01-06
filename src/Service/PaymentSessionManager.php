<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Magento\Quote\Model\Quote;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Service\PaymentSessionService;

class PaymentSessionManager
{
    /** @var PaymentSessionService */
    private $paymentSessionService;

    /**
     * @param PaymentSessionService $paymentSessionService
     */
    public function __construct(
        PaymentSessionService $paymentSessionService
    )
    {
        $this->paymentSessionService = $paymentSessionService;
    }


    /**
     * This method creates a payment session and returns a payment session id and redirect url, it will also do error
     * handling, where it dispatches a message to the component if an exception is thrown.
     * @param Quote $quote
     * @param string $checkoutId
     * @param Component $component
     * @return array
     */
    public function create(Quote $quote, string $checkoutId, Component $component): array
    {
        try {
            $paymentSession = $this->paymentSessionService->create($quote, $checkoutId);
            return [
                "paymentSessionId" => $paymentSession->getPaymentSessionId(),
                "redirectUrl" => $paymentSession->getRedirectUrl()
            ];
        } catch (\Exception $exception) {
            $detail = [
                'text' => $exception->getMessage(),
                'method' => $quote->getPayment()->getMethod(),
            ];

            $component->dispatchBrowserEvent('order:place:error', $detail);
            $component->dispatchBrowserEvent(sprintf('order:place:%s:error', $detail['method']), $detail);
            $component->dispatchErrorMessage($detail['text']);
        }
        return [];
    }
}
