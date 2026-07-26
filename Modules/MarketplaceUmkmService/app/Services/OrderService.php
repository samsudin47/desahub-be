<?php

namespace Modules\MarketplaceUmkmService\Services;

use Illuminate\Support\Facades\Storage;
use Modules\MarketplaceUmkmService\Models\Checkout;
use Modules\MarketplaceUmkmService\Models\CheckoutItem;
use Shared\Constants\CheckoutStatusConstantsHelper;

class OrderService
{
    /**
     * @return list<array{
     *     uuid: string,
     *     order_number: string,
     *     status: string,
     *     status_label: string,
     *     total_item: int,
     *     total_harga: int,
     *     created_at: string|null,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         harga_satuan: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string|null,
     *             nama_produk: string|null,
     *             harga: int,
     *             gambar: string|null
     *         }
     *     }>
     * }>
     */
    public function list(?string $status = null): array
    {
        $query = Checkout::query()
            ->notDeleted()
            ->where('uuid_user', getUserId())
            ->whereNotIn('status', [CheckoutStatusConstantsHelper::DRAFT])
            ->with([
                'checkoutItems' => fn ($q) => $q->notDeleted(),
                'checkoutItems.product:uuid,nama_product,harga,gambar',
                'latestPayment',
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
     * @return array{
     *     uuid: string,
     *     order_number: string,
     *     status: string,
     *     status_label: string,
     *     total_item: int,
     *     total_harga: int,
     *     created_at: string|null,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         harga_satuan: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string|null,
     *             nama_produk: string|null,
     *             harga: int,
     *             gambar: string|null
     *         }
     *     }>
     * }
     */
    private function formatOrder(Checkout $checkout): array
    {
        $items = $checkout->checkoutItems
            ->map(fn (CheckoutItem $item) => $this->formatOrderItem($item))
            ->values()
            ->all();

        return [
            'uuid' => $checkout->uuid,
            'order_number' => $checkout->latestPayment?->order_id
                ?? 'ORD-'.strtoupper(substr(str_replace('-', '', $checkout->uuid), 0, 8)),
            'status' => $checkout->status,
            'status_label' => $this->statusLabel($checkout->status),
            'total_item' => (int) $checkout->total_items,
            'total_harga' => (int) $checkout->total_price,
            'created_at' => $checkout->created_at?->toIso8601String(),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     uuid: string,
     *     quantity: int,
     *     harga_satuan: int,
     *     subtotal: int,
     *     produk: array{
     *         uuid: string|null,
     *         nama_produk: string|null,
     *         harga: int,
     *         gambar: string|null
     *     }
     * }
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

    private function statusLabel(string $status): string
    {
        return CheckoutStatusConstantsHelper::label($status);
    }
}
