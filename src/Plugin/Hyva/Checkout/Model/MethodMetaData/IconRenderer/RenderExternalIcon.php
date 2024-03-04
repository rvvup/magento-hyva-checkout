<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin\Hyva\Checkout\Model\MethodMetaData\IconRenderer;

use Hyva\Checkout\Model\MethodMetaData\IconRenderer;

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
        if (isset($logo['icon'])) {
            $icon = $logo['icon'];
            if (isset($icon['method_name']) && isset($icon['is_rvvup'])) {
                if ($icon['is_rvvup']) {
                    $methodName = $icon['method_name'];
                    $url = $icon['src'];
                    $displayName = $icon['display_name'];
                    return "<img class='max-h-11 w-11 $methodName' src='$url' alt='$displayName'/>";
                }
            }
        }

        return $proceed($logo);
    }
}
