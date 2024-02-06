<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Magewire\Product\View;

use Magewirephp\Magewire\Component;
use Rvvup\Payments\Model\ClearpayAvailability;

class Addtocart extends Component
{
    private ClearpayAvailability $clearpayAvailability;

    public bool $isAvailable = false;

    public function __construct(
        ClearpayAvailability $clearpayAvailability
    ) {
        $this->clearpayAvailability = $clearpayAvailability;
    }

    public function checkIfClearpayIsAvailable(): bool
    {
        return $this->clearpayAvailability->isAvailable();
    }
}
