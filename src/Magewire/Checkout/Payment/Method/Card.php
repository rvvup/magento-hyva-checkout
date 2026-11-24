<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magewire\Component;
use Rvvup\Payments\Model\SdkProxy;
use Rvvup\Payments\ViewModel\Assets;
use Rvvup\PaymentsHyvaCheckout\Service\SavedCardsAvailability;
use Rvvup\PaymentsHyvaCheckout\Service\SavedCardTokens;

class Card extends Component
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var Assets */
    private $assetsModel;

    /** @var SdkProxy */
    private $sdkProxy;

    /** @var SavedCardsAvailability */
    private $savedCardsAvailability;

    /** @var SavedCardTokens */
    private $savedCardTokens;

    /** @var array|null */
    private $savedCards;

    /** @var array */
    public $parameters = [];

    /**
     * @param SerializerInterface $serializer
     * @param Assets $assetsModel
     * @param SdkProxy $sdkProxy
     * @param SavedCardsAvailability $savedCardsAvailability
     * @param SavedCardTokens $savedCardTokens
     */
    public function __construct(
        SerializerInterface $serializer,
        Assets $assetsModel,
        SdkProxy $sdkProxy,
        SavedCardsAvailability $savedCardsAvailability,
        SavedCardTokens $savedCardTokens
    ) {
        $this->serializer = $serializer;
        $this->assetsModel = $assetsModel;
        $this->sdkProxy = $sdkProxy;
        $this->savedCardsAvailability = $savedCardsAvailability;
        $this->savedCardTokens = $savedCardTokens;
    }

    public function mount(): void
    {
        $this->parameters = $this->serializer->unserialize($this->assetsModel->getRvvupParametersJsObject());
    }

    public function showForm(): bool
    {
        return $this->parameters['settings']['card']['flow'] == 'INLINE';
    }

    public function showSaveCard(): bool
    {
        return $this->showForm() && $this->savedCardsAvailability->isAvailable($this->parameters);
    }

    /**
     * Rendered server side and never exposed as a public property, so the list is not sent to the browser
     * as component state.
     *
     * @return array[]
     */
    public function getSavedCards(): array
    {
        if ($this->savedCards === null) {
            $this->savedCards = $this->showForm() ? $this->savedCardTokens->getCards($this->parameters) : [];
        }

        return $this->savedCards;
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
