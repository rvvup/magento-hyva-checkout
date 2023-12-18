<?php declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Gateway\Command;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Rvvup\Payments\Hyva\Gateway\Method;
use Rvvup\Payments\Hyva\Model\ConfigInterface;
use Rvvup\Payments\Hyva\Model\SdkProxy;

class CreatePayment implements CommandInterface
{
    /** @var SdkProxy */
    private $sdkProxy;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param SdkProxy $sdkProxy
     * @param ConfigInterface $config
     */
    public function __construct(
        SdkProxy $sdkProxy,
        ConfigInterface $config
    ) {
        $this->sdkProxy = $sdkProxy;
        $this->config = $config;
    }

    public function execute(array $commandSubject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $commandSubject['payment'];
        $method = str_replace(Method::PAYMENT_TITLE_PREFIX, '', $payment->getMethod());
        $orderId = $payment->getAdditionalInformation()['rvvup_order_id'];
        $type = 'STANDARD';

        if ($payment->getAdditionalInformation('is_rvvup_express_payment')) {
            $type = 'EXPRESS';
        }
        $idempotencyKey = (string) time();

        $data = [
            'input' => [
            'orderId' => $orderId,
            'method' => $method,
            'type' => $type,
            'idempotencyKey' => $idempotencyKey,
            'merchantId' => $this->config->getMerchantId()
            ]
        ];

        if ($captureType = $payment->getMethodInstance()->getCaptureType()) {
            $data['input']['captureType'] = $captureType;
        }

        return $this->sdkProxy->createPayment(
            $data
        );
    }
}
