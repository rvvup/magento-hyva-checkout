<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin\Hyva\Checkout\Model\MethodMetaData;

use Hyva\Checkout\Model\MethodMetaData;
use Magento\Checkout\Model\Session;
use Rvvup\Payments\Model\SdkProxy;

class RenderMethodIcons
{
    private Session $checkoutSession;
    private SdkProxy $sdkProxy;

    private ?array $methods = null;

    public function __construct(
        Session $checkoutSession,
        SdkProxy $sdkProxy
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->sdkProxy = $sdkProxy;
    }

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
            'src' => $logo,
        ]);

        return true;
    }

    private function getLogo(string $method): ?string
    {
        if ($this->methods === null) {
            $quote = $this->checkoutSession->getQuote();
            $this->methods = $this->sdkProxy->getMethods((string)$quote->getGrandTotal(), $quote->getQuoteCurrencyCode());
        }

        // We get "rvvup_CARD" but we need "CARD"
        $methodName = substr($method, strlen('rvvup_'));
        foreach ($this->methods as $method) {
            if ($method['name'] == $methodName) {
                return $method['logoUrl'];
            }
        }

        return null;
    }
}
