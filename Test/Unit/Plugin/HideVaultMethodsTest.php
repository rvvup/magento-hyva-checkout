<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Test\Unit\Plugin;

use Hyva\Checkout\ViewModel\Checkout\Payment\MethodList;
use PHPUnit\Framework\TestCase;
use Rvvup\PaymentsHyvaCheckout\Model\RvvupVaultMethodFilter;
use Rvvup\PaymentsHyvaCheckout\Plugin\HideVaultMethods;

class HideVaultMethodsTest extends TestCase
{
    /** @var HideVaultMethods */
    private $plugin;

    /** @var MethodList */
    private $subject;

    protected function setUp(): void
    {
        $this->plugin = new HideVaultMethods(new RvvupVaultMethodFilter());
        $this->subject = $this->createMock(MethodList::class);
    }

    public function testRemovesTheVaultMethodFromTheList(): void
    {
        $result = $this->plugin->afterGetList(
            $this->subject,
            $this->methods(['checkmo', 'rvvup_CARD_vault', 'rvvup_CARD'])
        );

        $this->assertSame(['checkmo', 'rvvup_CARD'], $this->codes($result));
    }

    /**
     * getList() returns null when the payment methods of the quote cannot be loaded.
     */
    public function testNullIsLeftAsIs(): void
    {
        $this->assertNull($this->plugin->afterGetList($this->subject, null));
    }

    public function testEmptyList(): void
    {
        $this->assertSame([], $this->plugin->afterGetList($this->subject, []));
    }

    /**
     * @param string[] $codes
     * @return object[]
     */
    private function methods(array $codes): array
    {
        return array_map(static function (string $code) {
            return new class($code) {
                /** @var string */
                private $code;

                public function __construct(string $code)
                {
                    $this->code = $code;
                }

                public function getCode(): string
                {
                    return $this->code;
                }
            };
        }, $codes);
    }

    /**
     * @param object[] $methods
     * @return string[]
     */
    private function codes(array $methods): array
    {
        return array_values(array_map(static function ($method) {
            return $method->getCode();
        }, $methods));
    }
}
