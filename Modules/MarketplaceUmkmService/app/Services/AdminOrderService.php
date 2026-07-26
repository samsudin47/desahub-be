<?php

namespace Modules\MarketplaceUmkmService\Services;

use App\Facades\ResponseStandardAPI;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\MarketplaceUmkmService\Models\Checkout;
use Modules\MarketplaceUmkmService\Models\CheckoutItem;
use Modules\MarketplaceUmkmService\Models\CheckoutShipping;
use Shared\Constants\CheckoutStatusConstantsHelper;
use Shared\Constants\ResponseTypeConstantsHelper;

class AdminOrderService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function list(?string $status = null): array
    {
        $query = Checkout::query()
            ->notDeleted()
            ->whereNotIn('status', [CheckoutStatusConstantsHelper::DRAFT])
            ->with([
                'checkoutItems' => fn ($q) => $q->notDeleted(),
                'checkoutItems.product:uuid,nama_product,harga,gambar',
                'shipping',
                'latestPayment',
                'user:uuid,username,email',
            ])
            ->orderByDesc('created_at');

        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query
            ->get()
            ->map(fn (Checkout $checkout) => $this->formatOrder($checkout))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $uuid): array
    {
        return $this->formatOrder($this->findOrderOrFail($uuid));
    }

    /**
     * @return array<string, mixed>
     */
    public function process(string $uuid): array
    {
        return DB::transaction(function () use ($uuid) {
            $checkout = $this->findOrderOrFail($uuid, true);

            $this->ensureStatus(
                $checkout,
                [CheckoutStatusConstantsHelper::PAID],
                'Pesanan hanya dapat diproses dari status paid'
            );

            $checkout->update([
                'status' => CheckoutStatusConstantsHelper::PROCESSING,
                'updated_by' => getUserId(),
            ]);

            return $this->formatOrder($this->findOrderOrFail($checkout->uuid));
        });
    }

    /**
     * @param  array{courier: string, tracking_number: string}  $data
     * @return array<string, mixed>
     */
    public function ship(string $uuid, array $data): array
    {
        return DB::transaction(function () use ($uuid, $data) {
            $checkout = $this->findOrderOrFail($uuid, true);

            $this->ensureStatus(
                $checkout,
                [CheckoutStatusConstantsHelper::PROCESSING],
                'Pesanan hanya dapat dikirim dari status processing'
            );

            $shipping = CheckoutShipping::query()
                ->notDeleted()
                ->where('uuid_checkout', $checkout->uuid)
                ->lockForUpdate()
                ->first();

            if ($shipping === null) {
                throw new HttpResponseException(
                    ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                        ->info('Data pengiriman tidak ditemukan')
                        ->detail('Data pengiriman tidak ditemukan')
                        ->response()
                );
            }

            $shipping->update([
                'courier' => $data['courier'],
                'tracking_number' => $data['tracking_number'],
                'shipped_at' => now(),
                'updated_by' => getUserId(),
            ]);

            $checkout->update([
                'status' => CheckoutStatusConstantsHelper::SHIPPED,
                'updated_by' => getUserId(),
            ]);

            return $this->formatOrder($this->findOrderOrFail($checkout->uuid));
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(string $uuid): array
    {
        return DB::transaction(function () use ($uuid) {
            $checkout = $this->findOrderOrFail($uuid, true);

            $this->ensureStatus(
                $checkout,
                [CheckoutStatusConstantsHelper::SHIPPED],
                'Pesanan hanya dapat diselesaikan dari status shipped'
            );

            $shipping = CheckoutShipping::query()
                ->notDeleted()
                ->where('uuid_checkout', $checkout->uuid)
                ->lockForUpdate()
                ->first();

            if ($shipping !== null) {
                $shipping->update([
                    'completed_at' => now(),
                    'updated_by' => getUserId(),
                ]);
            }

            $checkout->update([
                'status' => CheckoutStatusConstantsHelper::COMPLETED,
                'updated_by' => getUserId(),
            ]);

            return $this->formatOrder($this->findOrderOrFail($checkout->uuid));
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(string $uuid, ?string $reason = null): array
    {
        return DB::transaction(function () use ($uuid, $reason) {
            $checkout = $this->findOrderOrFail($uuid, true);

            $this->ensureStatus(
                $checkout,
                [
                    CheckoutStatusConstantsHelper::PENDING,
                    CheckoutStatusConstantsHelper::PAID,
                    CheckoutStatusConstantsHelper::PROCESSING,
                ],
                'Pesanan tidak dapat dibatalkan pada status saat ini'
            );

            $shipping = CheckoutShipping::query()
                ->notDeleted()
                ->where('uuid_checkout', $checkout->uuid)
                ->lockForUpdate()
                ->first();

            if ($shipping !== null && $reason !== null && $reason !== '') {
                $shipping->update([
                    'cancel_reason' => $reason,
                    'updated_by' => getUserId(),
                ]);
            }

            $checkout->update([
                'status' => CheckoutStatusConstantsHelper::CANCELLED,
                'updated_by' => getUserId(),
            ]);

            return $this->formatOrder($this->findOrderOrFail($checkout->uuid));
        });
    }

    private function findOrderOrFail(string $uuid, bool $lock = false): Checkout
    {
        $query = Checkout::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->with([
                'checkoutItems' => fn ($q) => $q->notDeleted(),
                'checkoutItems.product:uuid,nama_product,harga,gambar',
                'shipping',
                'latestPayment',
                'user:uuid,username,email',
            ]);

        if ($lock) {
            $query->lockForUpdate();
        }

        $checkout = $query->first();

        if ($checkout === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Data pesanan tidak ditemukan')
                    ->detail('Data pesanan tidak ditemukan')
                    ->response()
            );
        }

        return $checkout;
    }

    /**
     * @param  list<string>  $allowedStatuses
     */
    private function ensureStatus(Checkout $checkout, array $allowedStatuses, string $message): void
    {
        if (in_array($checkout->status, $allowedStatuses, true)) {
            return;
        }

        $currentLabel = CheckoutStatusConstantsHelper::label($checkout->status);
        $allowedList = implode(', ', $allowedStatuses);

        throw new HttpResponseException(
            ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_VALIDATION)
                ->message('Validation failed')
                ->error('Invalid request data')
                ->validationFailed([
                    'status' => [
                        $message.'. Status saat ini: '.$checkout->status.' ('.$currentLabel.'). Status yang diizinkan: '.$allowedList.'.',
                    ],
                ])
                ->response()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrder(Checkout $checkout): array
    {
        $items = $checkout->checkoutItems
            ->map(fn (CheckoutItem $item) => $this->formatOrderItem($item))
            ->values()
            ->all();

        $shipping = $checkout->shipping;

        return [
            'uuid' => $checkout->uuid,
            'order_number' => $checkout->latestPayment?->order_id
                ?? 'ORD-'.strtoupper(substr(str_replace('-', '', $checkout->uuid), 0, 8)),
            'status' => $checkout->status,
            'status_label' => CheckoutStatusConstantsHelper::label($checkout->status),
            'total_item' => (int) $checkout->total_items,
            'total_harga' => (int) $checkout->total_price,
            'created_at' => $checkout->created_at?->toIso8601String(),
            'pembeli' => [
                'uuid' => $checkout->user?->uuid,
                'username' => $checkout->user?->username,
                'email' => $checkout->user?->email,
            ],
            'shipping' => $shipping === null ? null : [
                'nama_penerima' => $shipping->nama_penerima,
                'no_hp_penerima' => $shipping->no_hp_penerima,
                'alamat_penerima' => $shipping->alamat_penerima,
                'courier' => $shipping->courier,
                'tracking_number' => $shipping->tracking_number,
                'shipped_at' => $shipping->shipped_at?->toIso8601String(),
                'completed_at' => $shipping->completed_at?->toIso8601String(),
                'cancel_reason' => $shipping->cancel_reason,
            ],
            'payment' => [
                'order_id' => $checkout->latestPayment?->order_id,
                'payment_type' => $checkout->latestPayment?->payment_type,
                'status' => $checkout->latestPayment?->status,
            ],
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrderItem(CheckoutItem $item): array
    {
        $product = $item->product;

        return [
            'uuid' => $item->uuid,
            'quantity' => (int) $item->quantity,
            'harga_satuan' => (int) $item->harga_satuan,
            'subtotal' => (int) $item->subtotal,
            'produk' => [
                'uuid' => $product?->uuid,
                'nama_produk' => $product?->nama_product,
                'harga' => (int) $item->harga_satuan,
                'gambar' => $product?->gambar !== null
                    ? Storage::disk('public')->url($product->gambar)
                    : null,
            ],
        ];
    }
}
