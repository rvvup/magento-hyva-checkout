<?php declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model\ProcessOrder;

use Magento\Sales\Api\Data\OrderInterface;
use Rvvup\Payments\Hyva\Api\Data\ProcessOrderResultInterface;

interface ProcessorInterface
{
    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $rvvupData
     * @return \Rvvup\Payments\Hyva\Api\Data\ProcessOrderResultInterface
     */
    public function execute(OrderInterface $order, array $rvvupData): ProcessOrderResultInterface;
}
