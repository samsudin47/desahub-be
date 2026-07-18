<?php

namespace Modules\MarketplaceUmkmService\Services;

use App\Facades\ResponseStandardAPI;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\MarketplaceService\Models\Product;
use Modules\MarketplaceUmkmService\Models\Cart;
use Modules\MarketplaceUmkmService\Models\CartItem;
use Shared\Constants\ResponseTypeConstantsHelper;

class CartService
{
    /**
     * @return array{
     *     uuid: string|null,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string,
     *             nama_produk: string,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    public function getCart(): array
    {
        $cart = $this->findCartForUser();

        if ($cart === null) {
            return $this->emptyCartPayload();
        }

        return $this->formatCart($cart);
    }

    /**
     * @param  array{uuid_product: string, quantity?: int}  $data
     * @return array{
     *     uuid: string|null,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string,
     *             nama_produk: string,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    public function storeItem(array $data): array
    {
        $product = $this->findProductOrFail($data['uuid_product']);
        $quantity = (int) ($data['quantity'] ?? 1);

        return DB::transaction(function () use ($product, $quantity) {
            $cart = $this->getOrCreateCart();

            $cartItem = CartItem::query()
                ->notDeleted()
                ->where('uuid_cart', $cart->uuid)
                ->where('uuid_product', $product->uuid)
                ->first();

            $newQuantity = $cartItem !== null
                ? (int) $cartItem->quantity + $quantity
                : $quantity;

            $this->ensureStockIsSufficient($product, $newQuantity);

            if ($cartItem !== null) {
                $cartItem->update([
                    'quantity' => $newQuantity,
                    'updated_by' => getUserId(),
                ]);
            } else {
                CartItem::query()->create([
                    'uuid' => generateUuid(),
                    'uuid_cart' => $cart->uuid,
                    'uuid_product' => $product->uuid,
                    'quantity' => $newQuantity,
                    'is_deleted' => false,
                    'created_by' => getUserId(),
                ]);
            }

            return $this->formatCart($cart->fresh());
        });
    }

    /**
     * @param  array{quantity: int}  $data
     * @return array{
     *     uuid: string|null,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string,
     *             nama_produk: string,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    public function updateItem(string $uuid, array $data): array
    {
        $cartItem = $this->findCartItemOrFail($uuid);
        $product = $this->findProductOrFail($cartItem->uuid_product);
        $quantity = (int) $data['quantity'];

        $this->ensureStockIsSufficient($product, $quantity);

        $cartItem->update([
            'quantity' => $quantity,
            'updated_by' => getUserId(),
        ]);

        return $this->formatCart($cartItem->cart);
    }

    /**
     * @return array{
     *     uuid: string|null,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string,
     *             nama_produk: string,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    public function plusOrderItem(string $uuid): array
    {
        $cartItem = $this->findCartItemOrFail($uuid);
        $product = $this->findProductOrFail($cartItem->uuid_product);
        $quantity = (int) $cartItem->quantity + 1;

        $this->ensureStockIsSufficient($product, $quantity);

        $cartItem->update([
            'quantity' => $quantity,
            'updated_by' => getUserId(),
        ]);

        return $this->formatCart($cartItem->cart);
    }

    /**
     * @return array{
     *     uuid: string|null,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string,
     *             nama_produk: string,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    public function minusOrderItem(string $uuid): array
    {
        $cartItem = $this->findCartItemOrFail($uuid);

        if ((int) $cartItem->quantity <= 1) {
            return $this->destroyItem($uuid);
        }

        $cartItem->update([
            'quantity' => (int) $cartItem->quantity - 1,
            'updated_by' => getUserId(),
        ]);

        return $this->formatCart($cartItem->cart);
    }

    /**
     * @return array{
     *     uuid: string|null,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string,
     *             nama_produk: string,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    public function destroyItem(string $uuid): array
    {
        $cartItem = $this->findCartItemOrFail($uuid);
        $cart = $cartItem->cart;

        $cartItem->update([
            ...resourceData()->delete($cartItem),
            'deleted_by' => getUserId(),
        ]);

        return $this->formatCart($cart->fresh());
    }

    public function destroy(): void
    {
        $cart = $this->findCartForUser();

        if ($cart === null) {
            return;
        }

        DB::transaction(function () use ($cart) {
            CartItem::query()
                ->notDeleted()
                ->where('uuid_cart', $cart->uuid)
                ->get()
                ->each(function (CartItem $cartItem) {
                    $cartItem->update([
                        ...resourceData()->delete($cartItem),
                        'deleted_by' => getUserId(),
                    ]);
                });

            $cart->update([
                ...resourceData()->delete($cart),
                'deleted_by' => getUserId(),
            ]);
        });
    }

    private function findCartForUser(): ?Cart
    {
        return Cart::query()
            ->notDeleted()
            ->where('uuid_user', getUserId())
            ->first();
    }

    private function getOrCreateCart(): Cart
    {
        $cart = $this->findCartForUser();

        if ($cart !== null) {
            return $cart;
        }

        return Cart::query()->create([
            'uuid' => generateUuid(),
            'uuid_user' => getUserId(),
            'is_deleted' => false,
            'created_by' => getUserId(),
        ]);
    }

    private function findCartItemOrFail(string $uuid): CartItem
    {
        $cart = $this->findCartForUser();

        if ($cart === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Item keranjang tidak ditemukan')
                    ->detail('Item keranjang tidak ditemukan')
                    ->response()
            );
        }

        $cartItem = CartItem::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->where('uuid_cart', $cart->uuid)
            ->first();

        if ($cartItem === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Item keranjang tidak ditemukan')
                    ->detail('Item keranjang tidak ditemukan')
                    ->response()
            );
        }

        return $cartItem;
    }

    private function findProductOrFail(string $uuid): Product
    {
        return Product::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function ensureStockIsSufficient(Product $product, int $quantity): void
    {
        $stock = (int) $product->stock;

        if ($quantity <= $stock) {
            return;
        }

        $message = $stock <= 0
            ? 'Stok produk sudah habis.'
            : 'Jumlah melebihi stok yang tersedia ('.$stock.').';

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

    /**
     * @return array{
     *     uuid: null,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<empty>
     * }
     */
    private function emptyCartPayload(): array
    {
        return [
            'uuid' => null,
            'total_item' => 0,
            'total_harga' => 0,
            'items' => [],
        ];
    }

    /**
     * @return array{
     *     uuid: string,
     *     total_item: int,
     *     total_harga: int,
     *     items: list<array{
     *         uuid: string,
     *         quantity: int,
     *         subtotal: int,
     *         produk: array{
     *             uuid: string,
     *             nama_produk: string,
     *             harga: int,
     *             stock: int,
     *             gambar: string|null,
     *             penjual: array{uuid: string, nama: string|null}
     *         }
     *     }>
     * }
     */
    private function formatCart(Cart $cart): array
    {
        $items = CartItem::query()
            ->notDeleted()
            ->with(['product.penjual:uuid,nama_penjual'])
            ->where('uuid_cart', $cart->uuid)
            ->orderByDesc('created_at')
            ->get();

        $formattedItems = $items
            ->filter(fn (CartItem $item) => $item->product !== null && ! $item->product->is_deleted)
            ->map(fn (CartItem $item) => $this->formatCartItem($item))
            ->values()
            ->all();

        return [
            'uuid' => $cart->uuid,
            'total_item' => collect($formattedItems)->sum('quantity'),
            'total_harga' => collect($formattedItems)->sum('subtotal'),
            'items' => $formattedItems,
        ];
    }

    /**
     * @return array{
     *     uuid: string,
     *     quantity: int,
     *     subtotal: int,
     *     produk: array{
     *         uuid: string,
     *         nama_produk: string,
     *         harga: int,
     *         stock: int,
     *         gambar: string|null,
     *         penjual: array{uuid: string, nama: string|null}
     *     }
     * }
     */
    private function formatCartItem(CartItem $cartItem): array
    {
        $product = $cartItem->product;
        $harga = (int) $product->harga;
        $quantity = (int) $cartItem->quantity;

        return [
            'uuid' => $cartItem->uuid,
            'quantity' => $quantity,
            'subtotal' => $harga * $quantity,
            'produk' => [
                'uuid' => $product->uuid,
                'nama_produk' => $product->nama_product,
                'harga' => $harga,
                'stock' => (int) $product->stock,
                'gambar' => $product->gambar !== null
                    ? Storage::disk('public')->url($product->gambar)
                    : null,
                'penjual' => [
                    'uuid' => $product->uuid_penjual,
                    'nama' => $product->penjual?->nama_penjual,
                ],
            ],
        ];
    }
}
