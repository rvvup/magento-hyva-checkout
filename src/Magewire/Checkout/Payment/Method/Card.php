<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;

class Card extends Component
{
    private SerializerInterface $serializer;
    private Assets $assetsModel;
    private SdkProxy $sdkProxy;

    public array $parameters = [];

    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        SdkProxy $sdkProxy
    ) {
        $this->serializer = $serializer;
        $this->assetsModel = $assetsModel;
        $this->sdkProxy = $sdkProxy;
    }

    public function mount(): void
    {
        $this->parameters = $this->serializer->unserialize($this->assetsModel->getRvvupParametersJsObject());
    }

    public function showForm(): bool
    {
        return $this->parameters['settings']['card']['flow'] == 'INLINE';
    }

    public function showFrame(): bool
    {
        return !$this->showForm();
    }

    public function getIframeUrl(): string
    {
        foreach ($this->sdkProxy->getMethods() as $method) {
            if ($method['name'] == 'CARD') {
                return $method['summaryUrl'];
            }
        }

        throw new \Exception('Rvvup: No iframe URL found.');
    }
}
