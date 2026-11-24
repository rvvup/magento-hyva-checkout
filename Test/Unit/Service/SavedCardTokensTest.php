<?php

declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Test\Unit\Service;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use PHPUnit\Framework\TestCase;
use Rvvup\PaymentsHyvaCheckout\Service\SavedCardsAvailability;
use Rvvup\PaymentsHyvaCheckout\Service\SavedCardTokens;

class SavedCardTokensTest extends TestCase
{
    private const ENABLED = ['settings' => ['card' => ['savedCardsEnabled' => true]]];

    public function testReturnsOnlyUsableRvvupCards(): void
    {
        $tokens = [
            $this->token('good'),
            $this->token('inactive', ['active' => false]),
            $this->token('hidden', ['visible' => false]),
            $this->token('other', ['code' => 'braintree_cc']),
            $this->token('nogateway', ['gateway' => '']),
            $this->token('expired', ['expires' => '2001-01-01 00:00:00']),
            $this->token('future', ['expires' => '2999-01-01 00:00:00']),
        ];

        $cards = $this->create(true, $tokens)->getCards(self::ENABLED);

        $this->assertSame(['good', 'future'], array_column($cards, 'publicHash'));
        $this->assertSame(
            ['publicHash' => 'good', 'brand' => 'VI', 'last4' => '1111', 'expiry' => '12/2030'],
            $cards[0]
        );
        $this->assertArrayNotHasKey('gateway', $cards[0]);
    }

    public function testNothingForGuestOrDisabledMerchant(): void
    {
        $this->assertSame([], $this->create(false, [$this->token('good')])->getCards(self::ENABLED));
        $this->assertSame([], $this->create(true, [$this->token('good')])->getCards([]));
    }

    private function create(bool $loggedIn, array $tokens): SavedCardTokens
    {
        $session = $this->createMock(CustomerSession::class);
        $session->method('isLoggedIn')->willReturn($loggedIn);
        $session->method('getCustomerId')->willReturn(5);

        $management = $this->createMock(PaymentTokenManagementInterface::class);
        $management->method('getListByCustomerId')->willReturn($tokens);

        return new SavedCardTokens($management, $session, new SavedCardsAvailability($session));
    }

    private function token(string $hash, array $o = []): PaymentTokenInterface
    {
        $token = $this->createMock(PaymentTokenInterface::class);
        $token->method('getPublicHash')->willReturn($hash);
        $token->method('getIsVisible')->willReturn($o['visible'] ?? true);
        $token->method('getIsActive')->willReturn($o['active'] ?? true);
        $token->method('getPaymentMethodCode')->willReturn($o['code'] ?? 'rvvup_CARD');
        $token->method('getGatewayToken')->willReturn($o['gateway'] ?? 'tok_1');
        $token->method('getExpiresAt')->willReturn($o['expires'] ?? null);
        $token->method('getTokenDetails')->willReturn(
            json_encode(['type' => 'VI', 'maskedCC' => '1111', 'expirationDate' => '12/2030'])
        );

        return $token;
    }
}
