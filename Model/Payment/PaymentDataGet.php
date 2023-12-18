<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model\Payment;

use Psr\Log\LoggerInterface;
use Rvvup\Payments\Hyva\Model\SdkProxy;
use Throwable;

class PaymentDataGet implements PaymentDataGetInterface
{
    /**
     * @var \Rvvup\Payments\Hyva\Model\SdkProxy
     */
    private $sdkProxy;

    /**
     * @var \Psr\Log\LoggerInterface|RvvupLog
     */
    private $logger;

    /**
     * @param \Rvvup\Payments\Hyva\Model\SdkProxy $sdkProxy
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public function __construct(SdkProxy $sdkProxy, LoggerInterface $logger)
    {
        $this->sdkProxy = $sdkProxy;
        $this->logger = $logger;
    }

    /**
     * Get the Rvvup payment data from the API by Rvvup order ID.
     *
     * @param string $rvvupId
     * @return array
     */
    public function execute(string $rvvupId): array
    {
        try {
            return $this->sdkProxy->getOrder($rvvupId);
        } catch (Throwable $t) {
            $this->logger->error('Failed to get data from Rvvup for payment', ['rvvup_order_id' => $rvvupId]);
            return [];
        }
    }
}
