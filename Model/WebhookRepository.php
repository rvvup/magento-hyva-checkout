<?php declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model;

use Rvvup\Payments\Hyva\Api\WebhookRepositoryInterface;
use Rvvup\Payments\Hyva\Api\Data\WebhookInterface;
use Rvvup\Payments\Hyva\Api\Data\WebhookInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Rvvup\Payments\Hyva\Model\ResourceModel\WebhookResource;

class WebhookRepository implements WebhookRepositoryInterface
{
    /** @var WebhookResource */
    private $resource;
    /** @var WebhookInterfaceFactory */
    private $factory;

    /**
     * @param WebhookResource $resource
     * @param WebhookInterfaceFactory $factory
     */
    public function __construct(
        WebhookResource $resource,
        WebhookInterfaceFactory $factory
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
    }

    /**
     * @param array $data
     * @return WebhookInterface
     */
    public function new(array $data = []): WebhookInterface
    {
        $webhook = $this->factory->create(['data'=> $data]);
        $webhook->setDataChanges(true);
        return $webhook;
    }

    /**
     * @param WebhookInterface $webhook
     * @return WebhookInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(WebhookInterface $webhook): WebhookInterface
    {
        $this->resource->save($webhook);
        return $webhook;
    }

    /**
     * @param int $id
     * @return WebhookInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): WebhookInterface
    {
        $webhook = $this->factory->create();
        $this->resource->load($webhook, $id);
        if (!$webhook->getId()) {
            throw new NoSuchEntityException(__('Unable to find webhook with ID "%1"', $id));
        }
        return $webhook;
    }

    /**
     * @param WebhookInterface $webhook
     * @return WebhookInterface
     * @throws \Exception
     */
    public function delete(WebhookInterface $webhook): WebhookInterface
    {
        $this->resource->delete($webhook);
        return $webhook;
    }
}
