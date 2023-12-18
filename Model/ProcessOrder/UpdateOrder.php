<?php
declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model\ProcessOrder;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Rvvup\Payments\Hyva\Api\Data\ProcessOrderResultInterface;
use Rvvup\Payments\Hyva\Api\Data\ProcessOrderResultInterfaceFactory;
use Rvvup\Payments\Hyva\Controller\Redirect\In;
use Safe\Exceptions\JsonException;

class UpdateOrder implements ProcessorInterface
{
    /**
     * @var ProcessOrderResultInterfaceFactory
     */
    private $processOrderResultFactory;

    /**
     * @param ProcessOrderResultInterfaceFactory $processOrderResultFactory
     */
    public function __construct(
        ProcessOrderResultInterfaceFactory $processOrderResultFactory
    ) {
        $this->processOrderResultFactory = $processOrderResultFactory;
    }

    /**
     * @param OrderInterface $order
     * @param array $rvvupData
     * @return ProcessOrderResultInterface
     * @throws JsonException
     * @throws NoSuchEntityException
     */
    public function execute(OrderInterface $order, array $rvvupData): ProcessOrderResultInterface
    {
        $processOrderResult = $this->processOrderResultFactory->create();
        $processOrderResult->setResultType(ProcessOrderResultInterface::RESULT_TYPE_ERROR);
        $processOrderResult->setRedirectPath(In::FAILURE);
        return $processOrderResult;
    }
}
