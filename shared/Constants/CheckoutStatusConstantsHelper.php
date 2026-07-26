<?php

namespace Shared\Constants;

class CheckoutStatusConstantsHelper
{
    const DRAFT = 'draft';

    const PENDING = 'pending';

    const PAID = 'paid';

    const PROCESSING = 'processing';

    const SHIPPED = 'shipped';

    const COMPLETED = 'completed';

    const CANCELLED = 'cancelled';

    const FAILED = 'failed';

    const EXPIRED = 'expired';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::PENDING,
            self::PAID,
            self::PROCESSING,
            self::SHIPPED,
            self::COMPLETED,
            self::CANCELLED,
            self::FAILED,
            self::EXPIRED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function listFilter(): array
    {
        return [
            'all',
            self::PENDING,
            self::PAID,
            self::PROCESSING,
            self::SHIPPED,
            self::COMPLETED,
            self::CANCELLED,
            self::FAILED,
            self::EXPIRED,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::PENDING => 'Menunggu Pembayaran',
            self::PAID, self::PROCESSING => 'Diproses',
            self::SHIPPED => 'Dikirim',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
            self::FAILED => 'Gagal',
            self::EXPIRED => 'Kedaluwarsa',
            default => ucfirst($status),
        };
    }
}
