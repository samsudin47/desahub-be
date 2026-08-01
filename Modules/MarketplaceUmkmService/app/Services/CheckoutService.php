<?php

namespace Modules\MarketplaceUmkmService\Services;

use App\Facades\ResponseStandardAPI;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\MarketplaceService\Models\Product;
use Modules\MarketplaceUmkmService\Models\Cart;
use Modules\MarketplaceUmkmService\Models\CartItem;
use Modules\MarketplaceUmkmService\Models\Checkout;
use Modules\MarketplaceUmkmService\Models\CheckoutItem;
use Shared\Constants\ResponseTypeConstantsHelper;

class CheckoutService
{
    public function __construct(
        private CartService $cartService,
        private CheckoutPaymentService $checkoutPaymentService,
    ) {}

    /**
     * @param  array{cart_item_uuids: list<string>}  $data
     * @return array{
     *     uuid: string,
     *     status: string,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         harga_satuan: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string|null,
     *             nama_produk: string|null,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    public function store(array $data): array
    {
        $cart = $this->findCartForUserOrFail();
        $cartItems = $this->resolveCartItems($cart, $data['cart_item_uuids']);

        return DB::transaction(function () use ($cart, $cartItems) {
            $this->ensureCartItemsNotAlreadyInPendingCheckout($cartItems);
            $this->ensureStockIsAvailableForCheckout($cartItems);

            $checkoutItemsPayload = $cartItems->map(function (CartItem $cartItem) {
                $product = $this->findValidProductOrFail($cartItem);
                $quantity = (int) $cartItem->quantity;
                $hargaSatuan = (int) $product->harga;

                return [
                    'uuid' => generateUuid(),
                    'uuid_cart_item' => $cartItem->uuid,
                    'uuid_product' => $product->uuid,
                    'uuid_penjual' => $product->uuid_penjual,
                    'quantity' => $quantity,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal' => $hargaSatuan * $quantity,
                    'is_deleted' => false,
                    'created_by' => getUserId(),
                ];
            });

            $checkout = Checkout::query()->create([
                'uuid' => generateUuid(),
                'uuid_user' => getUserId(),
                'uuid_cart' => $cart->uuid,
                'total_items' => $checkoutItemsPayload->sum('quantity'),
                'total_price' => $checkoutItemsPayload->sum('subtotal'),
                'status' => 'pending',
                'is_deleted' => false,
                'created_by' => getUserId(),
            ]);

            $checkoutItemsPayload->each(function (array $item) use ($checkout) {
                CheckoutItem::query()->create([
                    ...$item,
                    'uuid_checkout' => $checkout->uuid,
                ]);
            });

            $cartItems->each(function (CartItem $cartItem) {
                $cartItem->update([
                    ...resourceData()->delete($cartItem),
                    'deleted_by' => getUserId(),
                ]);
            });

            return $this->formatCheckout(
                $this->findCheckoutForUserOrFail($checkout->uuid)
            );
        });
    }

    /**
     * @return array{
     *     uuid: string,
     *     status: string,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         harga_satuan: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string|null,
     *             nama_produk: string|null,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    public function show(string $uuid): array
    {
        return $this->formatCheckout(
            $this->findCheckoutForUserOrFail($uuid)
        );
    }

    /**
     * @return array{
     *     checkout: array{
     *         uuid: string,
     *         status: string,
     *         total_item: int,
     *         total_harga: int,
     *         items: list<array{
     *             uuid: string,
     *             quantity: int,
     *             harga_satuan: int,
     *             subtotal: int,
     *             produk: array{
     *                 uuid: string|null,
     *                 nama_produk: string|null,
     *                 harga: int,
     *                 stock: int,
     *                 gambar: string|null,
     *                 penjual: array{uuid: string, nama: string|null}
     *             }
     *         }>
     *     },
     *     cart: array{
     *         uuid: string|null,
     *         total_item: int,
     *         total_harga: int,
     *         items: list<mixed>
     *     }
     * }
     */
    public function cancel(string $uuid): array
    {
        return DB::transaction(function () use ($uuid) {
            $checkout = $this->findPendingCheckoutForUserOrFail($uuid);

            $this->checkoutPaymentService->cancelPendingPaymentsForCheckout(
                $checkout->uuid,
                'checkout_cancelled_by_user'
            );

            $checkout->update([
                'status' => 'cancelled',
                'updated_by' => getUserId(),
            ]);

            $this->restoreCartItemsFromCheckout($checkout);

            return [
                'checkout' => $this->formatCheckout(
                    $checkout->fresh([
                        'checkoutItems' => fn ($query) => $query->notDeleted(),
                        'checkoutItems.product.penjual:uuid,nama_penjual',
                    ])
                ),
                'cart' => $this->cartService->getCart(),
            ];
        });
    }

    private function findCartForUserOrFail(): Cart
    {
        $cart = Cart::query()
            ->notDeleted()
            ->where('uuid_user', getUserId())
            ->first();

        if ($cart === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Keranjang tidak ditemukan')
                    ->detail('Keranjang tidak ditemukan')
                    ->response()
            );
        }

        return $cart;
    }

    /**
     * @param  list<string>  $cartItemUuids
     * @return Collection<int, CartItem>
     */
    private function resolveCartItems(Cart $cart, array $cartItemUuids): Collection
    {
        $cartItems = CartItem::query()
            ->notDeleted()
            ->with(['product.penjual:uuid,nama_penjual'])
            ->where('uuid_cart', $cart->uuid)
            ->whereIn('uuid', $cartItemUuids)
            ->get();

        if ($cartItems->count() !== count($cartItemUuids)) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Item keranjang tidak ditemukan')
                    ->detail('Item keranjang tidak ditemukan')
                    ->response()
            );
        }

        return $cartItems;
    }

    private function findValidProductOrFail(CartItem $cartItem): Product
    {
        $product = $cartItem->product;

        if ($product === null || $product->is_deleted) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Produk tidak tersedia')
                    ->detail('Produk tidak tersedia')
                    ->response()
            );
        }

        return $product;
    }

    private function findCheckoutForUserOrFail(string $uuid): Checkout
    {
        $checkout = Checkout::query()
            ->notDeleted()
            ->with([
                'checkoutItems' => fn ($query) => $query->notDeleted(),
                'checkoutItems.product.penjual:uuid,nama_penjual',
            ])
            ->where('uuid_user', getUserId())
            ->where('uuid', $uuid)
            ->first();

        if ($checkout === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Data checkout tidak ditemukan')
                    ->detail('Data checkout tidak ditemukan')
                    ->response()
            );
        }

        return $checkout;
    }

    private function findPendingCheckoutForUserOrFail(string $uuid): Checkout
    {
        $checkout = $this->findCheckoutForUserOrFail($uuid);

        if ($checkout->status !== 'pending') {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Checkout tidak dapat dibatalkan')
                    ->detail('Checkout tidak dapat dibatalkan')
                    ->response()
            );
        }

        return $checkout;
    }

    private function restoreCartItemsFromCheckout(Checkout $checkout): void
    {
        foreach ($checkout->checkoutItems as $checkoutItem) {
            $originalCartItem = CartItem::query()
                ->where('uuid', $checkoutItem->uuid_cart_item)
                ->where('uuid_cart', $checkout->uuid_cart)
                ->first();

            if ($originalCartItem === null) {
                continue;
            }

            $activeCartItem = CartItem::query()
                ->notDeleted()
                ->where('uuid_cart', $checkout->uuid_cart)
                ->where('uuid_product', $checkoutItem->uuid_product)
                ->where('uuid', '!=', $originalCartItem->uuid)
                ->first();

            if ($activeCartItem !== null) {
                $activeCartItem->update([
                    'quantity' => (int) $activeCartItem->quantity + (int) $checkoutItem->quantity,
                    'updated_by' => getUserId(),
                ]);

                continue;
            }

            if ($originalCartItem->is_deleted) {
                $originalCartItem->update([
                    'is_deleted' => false,
                    'deleted_by' => null,
                    'updated_by' => getUserId(),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, CartItem>  $cartItems
     */
    private function ensureCartItemsNotAlreadyInPendingCheckout(Collection $cartItems): void
    {
        $alreadyInPendingCheckout = CheckoutItem::query()
            ->notDeleted()
            ->whereIn('uuid_cart_item', $cartItems->pluck('uuid'))
            ->whereHas('checkout', fn ($query) => $query
                ->notDeleted()
                ->where('status', 'pending'))
            ->exists();

        if (! $alreadyInPendingCheckout) {
            return;
        }

        throw new HttpResponseException(
            ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                ->info('Item keranjang sudah dalam proses checkout')
                ->detail('Item keranjang sudah dalam proses checkout')
                ->response()
        );
    }

    /**
     * @param  Collection<int, CartItem>  $cartItems
     */
    private function ensureStockIsAvailableForCheckout(Collection $cartItems): void
    {
        $requestedByProduct = $cartItems
            ->groupBy('uuid_product')
            ->map(fn (Collection $items) => (int) $items->sum('quantity'));

        foreach ($requestedByProduct as $productUuid => $requestedQuantity) {
            $product = Product::query()
                ->notDeleted()
                ->where('uuid', $productUuid)
                ->lockForUpdate()
                ->first();

            if ($product === null) {
                throw new HttpResponseException(
                    ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                        ->info('Produk tidak tersedia')
                        ->detail('Produk tidak tersedia')
                        ->response()
                );
            }

            $reservedQuantity = $this->getPendingReservedQuantity($productUuid);
            $availableStock = (int) $product->stock - $reservedQuantity;

            if ($requestedQuantity <= $availableStock) {
                continue;
            }

            $message = $availableStock <= 0
                ? 'Stok produk sudah habis.'
                : 'Jumlah melebihi stok yang tersedia ('.$availableStock.').';

            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_VALIDATION)
                    ->message('Validation failed')
                    ->error('Invalid request data')
                    ->validationFailed([
                        'quantity' => [$message],
                    ])
                    ->response()
            );
        }
    }

    private function getPendingReservedQuantity(string $productUuid): int
    {
        return (int) CheckoutItem::query()
            ->notDeleted()
            ->where('uuid_product', $productUuid)
            ->whereHas('checkout', fn ($query) => $query
                ->notDeleted()
                ->where('status', 'pending'))
            ->sum('quantity');
    }

    /**
     * @return array{
     *     uuid: string,
     *     status: string,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         harga_satuan: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string|null,
     *             nama_produk: string|null,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    private function formatCheckout(Checkout $checkout): array
    {
        $formattedItems = $checkout->checkoutItems
            ->map(fn (CheckoutItem $item) => $this->formatCheckoutItem($item))
            ->values()
            ->all();

        return [
            'uuid' => $checkout->uuid,
            'status' => $checkout->status,
            'total_item' => collect($formattedItems)->sum('quantity'),
            'total_harga' => collect($formattedItems)->sum('subtotal'),
            'items' => $formattedItems,
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
     *         stock: int,
     *         gambar: string|null,
     *         penjual: array{uuid: string, nama: string|null}
     *     }
     * }
     */
    private function formatCheckoutItem(CheckoutItem $checkoutItem): array
    {
        $product = $checkoutItem->product;

        return [
            'uuid' => $checkoutItem->uuid,
            'quantity' => (int) $checkoutItem->quantity,
            'harga_satuan' => (int) $checkoutItem->harga_satuan,
            'subtotal' => (int) $checkoutItem->subtotal,
            'produk' => [
                'uuid' => $product?->uuid,
                'nama_produk' => $product?->nama_product,
                'harga' => (int) $checkoutItem->harga_satuan,
                'stock' => (int) ($product?->stock ?? 0),
                'gambar' => $product?->gambar !== null
                    ? Storage::disk('public')->url($product->gambar)
                    : null,
                'penjual' => [
                    'uuid' => $checkoutItem->uuid_penjual,
                    'nama' => $product?->penjual?->nama_penjual,
                ],
            ],
        ];
    }
}
