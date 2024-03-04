<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Service;

use Rvvup\Payments\Model\PaymentAction;
use Rvvup\Payments\Model\PaymentActionFactory;
use Rvvup\Payments\Model\PaymentActionsGetInterface;

class GetPaymentActions
{
    /** @var PaymentActionsGetInterface */
    private $paymentActionsGet;

    /** @var GetPaymentsActionsResultFactory */
    private $getPaymentsActionsResultFactory;

    /** @var PaymentActionFactory */
    private $paymentActionFactory;

    /**
     * @param PaymentActionsGetInterface $paymentActionsGet
     * @param GetPaymentsActionsResultFactory $getPaymentsActionsResultFactory
     * @param PaymentActionFactory $paymentActionFactory
     */
    public function __construct(
        PaymentActionsGetInterface $paymentActionsGet,
        GetPaymentsActionsResultFactory $getPaymentsActionsResultFactory,
        PaymentActionFactory $paymentActionFactory
    ) {
        $this->paymentActionsGet = $paymentActionsGet;
        $this->getPaymentsActionsResultFactory = $getPaymentsActionsResultFactory;
        $this->paymentActionFactory = $paymentActionFactory;
    }

    public function execute(int $quoteId): GetPaymentsActionsResult
    {
        $paymentAction = $this->paymentActionsGet->execute((string)$quoteId);
        $authorizationAction = $this->findByType($paymentAction, 'authorization');
        $cancelAction = $this->findByType($paymentAction, 'cancel');
        $captureAction = $this->findByType($paymentAction, 'capture');

        return $this->getPaymentsActionsResultFactory->create([
            'authorization' => $authorizationAction,
            'cancel' => $cancelAction,
            'capture' =>  $captureAction,
        ]);
    }

    /**
     * @param PaymentAction[] $paymentAction
     */
    private function findByType(array $paymentAction, string $type): ?PaymentAction
    {
        foreach ($paymentAction as $action) {
            if (is_array($action)) {
                $action = $this->paymentActionFactory->create()->setData($action);
            }

            if ($action->getType() === $type) {
                return $action;
            }
        }

        return null;
    }
}
