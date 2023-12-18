<?php declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Api;

interface ClearpayAvailabilityInterface
{
    /**
     * @return bool
     */
    public function isAvailable(): bool;
}
