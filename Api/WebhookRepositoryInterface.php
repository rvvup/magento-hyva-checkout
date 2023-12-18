<?php declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Api;

use Rvvup\Payments\Hyva\Api\Data\WebhookInterface;

interface WebhookRepositoryInterface
{
    /**
     * @param array $data
     * @return WebhookInterface
     */
    public function new(array $data = []): WebhookInterface;

    /**
     * @param WebhookInterface $webhook
     * @return WebhookInterface
     */
    public function save(WebhookInterface $webhook): WebhookInterface;

    /**
     * @param int $id
     * @return WebhookInterface
     */
    public function getById(int $id): WebhookInterface;

    /**
     * @param WebhookInterface $webhook
     * @return WebhookInterface
     */
    public function delete(WebhookInterface $webhook): WebhookInterface;
}
