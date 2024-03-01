<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin\Hyva\Checkout\Model\MethodMetaData\IconRenderer;

use Hyva\Checkout\Model\MethodMetaData\IconRenderer;

class RenderExternalIcon
{
    /**
     * Hyvä does not support external images. So render it ourselves.
     *
     * @param IconRenderer $subject
     * @param string $result
     * @param string $url
     * @return string
     */
    public function afterRenderAsImage(IconRenderer $subject, string $result, string $url): string
    {
        if (strpos($url, 'rvvup') === false || strpos($url, 'https://') === false) {
            return $result;
        }

        return '<img class="max-h-11 w-11" src="' . $url . '" alt="Rvvup Payment Method" />';
    }
}
