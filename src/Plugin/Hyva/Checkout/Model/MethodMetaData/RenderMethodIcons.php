<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin\Hyva\Checkout\Model\MethodMetaData;

use Hyva\Checkout\Model\MethodMetaData;

class RenderMethodIcons
{
    public function afterCanRenderIcon(MethodMetaData $subject, bool $result): bool
    {
        $code = $subject->getMethod()->getCode();
        if (strpos($code, 'rvvup_') !== 0) {
            return $result;
        }

        $logo = $this->getLogo($code);
        if ($logo == null) {
            return $result;
        }

        $subject->setData('icon', [
            'svg' => $logo,
        ]);

        return true;
    }

    private function getLogo(string $method): ?string
    {
        // We get "rvvup_CARD" but we need "card"
        $methodName = strtolower(substr($method, strlen('rvvup_')));

        return 'rvvup_payments/' . $methodName;
    }
}
