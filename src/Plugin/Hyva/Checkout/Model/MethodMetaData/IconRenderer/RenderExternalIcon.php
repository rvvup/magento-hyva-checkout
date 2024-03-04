<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin\Hyva\Checkout\Model\MethodMetaData\IconRenderer;

use Hyva\Checkout\Model\MethodMetaData\IconRenderer;
use Rvvup\Payments\Gateway\Method;

class RenderExternalIcon
{

    /**
     * @param IconRenderer $subject
     * @param callable $proceed
     * @param array $logo
     * @return string
     */
    public function aroundRender(IconRenderer $subject, callable $proceed, array $logo): string
    {
        if (isset($logo['src'])) {
            if (isset($logo['method_name']) && isset($logo['is_rvvup'])) {
                if ($logo['is_rvvup']) {
                    $methodName = strtolower(Method::PAYMENT_TITLE_PREFIX . $logo['method_name'] . '_img');
                    $url = $logo['src'];
                    $displayName = $logo['display_name'];
                    return "<img class='max-h-11 $methodName' src='$url' alt='$displayName'/>";
                }
            }
        }

        return $proceed($logo);
    }
}
