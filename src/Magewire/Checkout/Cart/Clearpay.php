<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Checkout\Cart;

use Magewirephp\Magewire\Component;
use Rvvup\Payments\Model\ClearpayAvailability;

class Clearpay extends Component
{
    private ClearpayAvailability $clearpayAvailability;

    public bool $isClearpayAvailable = false;

    public function __construct(
        ClearpayAvailability $clearpayAvailability
    ) {
        $this->clearpayAvailability = $clearpayAvailability;
    }

    public function load(): void
    {
        $this->isClearpayAvailable = $this->clearpayAvailability->isAvailable();
    }
}
