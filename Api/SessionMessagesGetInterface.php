<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Api;

interface SessionMessagesGetInterface
{
    /**
     * Get the Rvvup Payments session messages.
     *
     * @return \Rvvup\Payments\Hyva\Api\Data\SessionMessageInterface[]
     */
    public function execute(): array;
}
