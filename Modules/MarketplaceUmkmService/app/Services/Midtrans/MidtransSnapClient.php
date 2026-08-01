<?php

namespace Modules\MarketplaceUmkmService\Services\Midtrans;

use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction;
use RuntimeException;
use Throwable;

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

    /**
     * @return array<string, mixed>
     */
    public function getTransactionStatus(string $orderId): array
    {
        $this->configure();

        $status = Transaction::status($orderId);

        if (is_object($status)) {
            $status = json_decode(json_encode($status), true);
        }

        if (! is_array($status)) {
            throw new RuntimeException('Respons status Midtrans tidak valid.');
        }

        return $status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function cancelTransaction(string $orderId): ?array
    {
        $this->configure();

        try {
            $result = Transaction::cancel($orderId);

            if (is_object($result)) {
                $result = json_decode(json_encode($result), true);
            }

            return is_array($result) ? $result : null;
        } catch (Throwable) {
            // Sudah expire/cancel di Midtrans: abaikan
            return null;
        }
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
