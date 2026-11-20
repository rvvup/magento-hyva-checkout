<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Rvvup\PaymentsHyvaCheckout\Model\RvvupVaultMethodFilter;

class RvvupVaultMethodFilterTest extends TestCase
{
    /** @var RvvupVaultMethodFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new RvvupVaultMethodFilter();
    }

    public function testRemovesRvvupCardVault(): void
    {
        $result = $this->filter->filter($this->methods(['checkmo', 'rvvup_CARD_vault', 'rvvup_CARD', 'rvvup_YAPILY']));

        $this->assertSame(['checkmo', 'rvvup_CARD', 'rvvup_YAPILY'], $this->codes($result));
    }

    public function testRemovesAnyRvvupVaultWrapper(): void
    {
        $result = $this->filter->filter($this->methods(['rvvup_PAYPAL_vault', 'rvvup_PAYPAL']));

        $this->assertSame(['rvvup_PAYPAL'], $this->codes($result));
    }

    public function testKeepsVaultMethodsFromOtherModules(): void
    {
        $result = $this->filter->filter($this->methods(['braintree_cc_vault', 'paypal_billing_agreement', 'vault']));

        $this->assertSame(['braintree_cc_vault', 'paypal_billing_agreement', 'vault'], $this->codes($result));
    }

    public function testDoesNotMatchBySuffixAlone(): void
    {
        $this->assertFalse($this->filter->isRvvupVaultMethod('other_CARD_vault'));
        $this->assertFalse($this->filter->isRvvupVaultMethod('rvvup_CARD'));
        $this->assertFalse($this->filter->isRvvupVaultMethod('rvvup_vault'));
        $this->assertTrue($this->filter->isRvvupVaultMethod('rvvup_CARD_vault'));
    }

    public function testResultIsReindexedList(): void
    {
        $result = $this->filter->filter($this->methods(['rvvup_CARD_vault', 'checkmo']));

        $this->assertSame([0], array_keys($result));
    }

    public function testEmptyList(): void
    {
        $this->assertSame([], $this->filter->filter([]));
    }

    public function testNoVaultMethodLeavesListUnchanged(): void
    {
        $methods = $this->methods(['checkmo', 'rvvup_CARD']);

        $this->assertSame($methods, $this->filter->filter($methods));
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
