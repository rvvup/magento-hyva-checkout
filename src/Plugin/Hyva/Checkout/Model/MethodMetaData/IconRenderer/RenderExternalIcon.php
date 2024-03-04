<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin\Hyva\Checkout\Model\MethodMetaData\IconRenderer;

use Hyva\Checkout\Model\MethodMetaData\IconRenderer;

class RenderExternalIcon
{

    /**
     * @param IconRenderer $subject
     * @param string $result
     * @param array $logo
     * @return string
     */
    public function afterRender(IconRenderer $subject, string $result, array $logo): string
    {
        if (isset($logo['method_name']) && isset($logo['is_rvvup'])) {
            if ($logo['is_rvvup']) {
                $methodName = $logo['method_name'];
                $url = $logo['url'];
                $displayName = $logo['display_name'];
                return "<img class='max-h-11 w-11 $methodName' src='$url' alt='$displayName'/>";
            }
        }

        return $result;
    }
}
