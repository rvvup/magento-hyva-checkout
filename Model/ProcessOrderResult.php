<?php

declare(strict_types=1);

namespace Rvvup\Payments\Hyva\Model;

use Magento\Framework\DataObject;
use Rvvup\Payments\Hyva\Api\Data\ProcessOrderResultInterface;

class ProcessOrderResult extends DataObject implements ProcessOrderResultInterface
{
    /**
     * Get the result type.
     *
     * @return string|null
     */
    public function getResultType(): ?string
    {
        return $this->getData(self::RESULT_TYPE);
    }

    /**
     * Set the result type.
     *
     * Do not set a value if not one of the allowed ones.
     *
     * @param string $resultType
     * @return void
     */
    public function setResultType(string $resultType): void
    {
        if (!in_array($resultType, $this->getResultTypes(), true)) {
            return;
        }

        $this->setData(self::RESULT_TYPE, $resultType);
    }

    /**
     * Get the expected Redirect Path.
     *
     * @return string|null
     */
    public function getRedirectPath(): ?string
    {
        return $this->getData(self::REDIRECT_PATH);
    }

    /**
     * Set the expected Redirect Path.
     *
     * @param string $redirectPath
     * @return void
     */
    public function setRedirectPath(string $redirectPath): void
    {
        $this->setData(self::REDIRECT_PATH, $redirectPath);
    }

    /**
     * Get the customer message.
     *
     * @return string|null
     */
    public function getCustomerMessage(): ?string
    {
        return $this->getData(self::CUSTOMER_MESSAGE);
    }

    /**
     * Set the customer message.
     *
     * @param string $customerMessage
     * @return void
     */
    public function setCustomerMessage(string $customerMessage): void
    {
        $this->setData(self::CUSTOMER_MESSAGE, $customerMessage);
    }

    /**
     * Get the default result types.
     *
     * @return array
     */
    private function getResultTypes(): array
    {
        return [
            self::RESULT_TYPE_SUCCESS,
            self::RESULT_TYPE_ERROR
        ];
    }
}
