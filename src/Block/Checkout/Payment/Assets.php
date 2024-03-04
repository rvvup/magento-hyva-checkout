<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Block\Checkout\Payment;

use Magento\Framework\View\Element\Template;
use Rvvup\Payments\ViewModel\Assets as AssetsModel;

class Assets extends Template
{
    /** @var AssetsModel  */
    private $assetsModel;

    /**
     * @param Template\Context $context
     * @param AssetsModel $assetsModel
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        AssetsModel $assetsModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->assetsModel = $assetsModel;
    }

    /**
     * @return array{
     *     url: string,
     *     attributes: array<string, string>,
     * }
     */
    public function getAssets(): array
    {
        $output = [];

        foreach ($this->assetsModel->getPaymentMethodsScriptAssets() as $type => $assets) {
            foreach ($assets as $asset) {
                $output[] = [
                    'url' => $asset['url'],
                    'attributes' => $asset['attributes'],
                ];
            }
        }

        return $output;
    }
}
