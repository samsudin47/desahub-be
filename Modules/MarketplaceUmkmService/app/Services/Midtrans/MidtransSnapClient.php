<?php

namespace Modules\MarketplaceUmkmService\Services\Midtrans;

use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use RuntimeException;

class MidtransSnapClient
{
    public function __construct()
    {
        $this->configure();
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function createSnapToken(array $params): string
    {
        $token = Snap::getSnapToken($params);

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Gagal membuat Snap token Midtrans.');
        }

        return $token;
    }

    public function isValidSignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $signatureKey
    ): bool {
        $serverKey = (string) config('services.midtrans.server_key');
        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signatureKey);
    }

    public function clientKey(): string
    {
        return (string) config('services.midtrans.client_key');
    }

    public function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production', false);
    }

    private function configure(): void
    {
        MidtransConfig::$serverKey = (string) config('services.midtrans.server_key');
        MidtransConfig::$isProduction = $this->isProduction();
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }
}
