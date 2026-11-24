<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Test\Unit\Service;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use PHPUnit\Framework\TestCase;
use Rvvup\Payments\Gateway\Method;
use Rvvup\PaymentsHyvaCheckout\Service\SavedCardsAvailability;

class SavedCardsAvailabilityTest extends TestCase
{
    private const ENABLED = ['settings' => ['card' => ['savedCardsEnabled' => true]]];

    public function testAvailableWhenEnabledAndLoggedIn(): void
    {
        $this->assertTrue($this->create(true)->isAvailable(self::ENABLED));
    }

    public function testUnavailableForGuest(): void
    {
        $this->assertFalse($this->create(false)->isAvailable(self::ENABLED));
    }

    /**
     * @dataProvider disabledSettingsProvider
     */
    public function testUnavailableWhenNotEnabled(array $parameters): void
    {
        $this->assertFalse($this->create(true)->isAvailable($parameters));
    }

    public function disabledSettingsProvider(): array
    {
        return [
            'flag false' => [['settings' => ['card' => ['savedCardsEnabled' => false]]]],
            'flag missing' => [['settings' => ['card' => ['flow' => 'INLINE']]]],
            'no settings' => [[]],
        ];
    }

    public function testApplyStoresChoiceWhenAllowed(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->expects($this->once())->method('setAdditionalInformation')
            ->with(Method::SAVE_PAYMENT_METHOD, true);
        $payment->expects($this->never())->method('unsAdditionalInformation');

        $this->create(true)->apply($this->quote($payment), self::ENABLED, true);
    }

    public function testApplyClearsStaleChoiceWhenNotRequested(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->expects($this->never())->method('setAdditionalInformation');
        $payment->expects($this->once())->method('unsAdditionalInformation')
            ->with(Method::SAVE_PAYMENT_METHOD);

        $this->create(true)->apply($this->quote($payment), self::ENABLED, false);
    }

    public function testApplyNeverStoresChoiceForGuest(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->expects($this->never())->method('setAdditionalInformation');
        $payment->expects($this->once())->method('unsAdditionalInformation')
            ->with(Method::SAVE_PAYMENT_METHOD);

        $this->create(false)->apply($this->quote($payment), self::ENABLED, true);
    }

    public function testApplyNeverStoresChoiceWhenFeatureDisabled(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->expects($this->never())->method('setAdditionalInformation');
        $payment->expects($this->once())->method('unsAdditionalInformation');

        $this->create(true)->apply($this->quote($payment), [], true);
    }

    public function testApplySavedCardStoresHashAndDropsSaveChoice(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->expects($this->once())->method('setAdditionalInformation')
            ->with(Method::PUBLIC_HASH, 'abc');
        $payment->expects($this->once())->method('unsAdditionalInformation')
            ->with(Method::SAVE_PAYMENT_METHOD);

        $this->create(true)->applySavedCard($this->quote($payment), self::ENABLED, 'abc');
    }

    public function testApplySavedCardClearsStaleHashForNewCard(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->expects($this->never())->method('setAdditionalInformation');
        $payment->expects($this->once())->method('unsAdditionalInformation')
            ->with(Method::PUBLIC_HASH);

        $this->create(true)->applySavedCard($this->quote($payment), self::ENABLED, '');
    }

    public function testApplySavedCardRejectsGuest(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->expects($this->never())->method('setAdditionalInformation');
        $payment->expects($this->once())->method('unsAdditionalInformation')
            ->with(Method::PUBLIC_HASH);

        $this->expectException(LocalizedException::class);
        $this->create(false)->applySavedCard($this->quote($payment), self::ENABLED, 'abc');
    }

    private function create(bool $loggedIn): SavedCardsAvailability
    {
        $session = $this->createMock(CustomerSession::class);
        $session->method('isLoggedIn')->willReturn($loggedIn);

        return new SavedCardsAvailability($session);
    }

    private function quote(Payment $payment): Quote
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getPayment')->willReturn($payment);

        return $quote;
    }
}
