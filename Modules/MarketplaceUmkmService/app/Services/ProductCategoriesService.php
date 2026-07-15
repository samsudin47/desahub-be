<?php

namespace Modules\MarketplaceUmkmService\Services;

use Illuminate\Support\Facades\Storage;
use Modules\DataManagement\Models\MasterKategori;
use Modules\MarketplaceService\Models\Product;

class ProductCategoriesService
{
    /**
     * @return array{
     *     uuid: string,
     *     nama_kategori: string,
     *     total_produk: int,
     *     produk: list<array{
     *         uuid: string,
     *         nama_produk: string,
     *         deskripsi: string,
     *         harga: int,
     *         rating: null,
     *         stock: int,
     *         gambar: string|null,
     *         penjual: array{uuid: string, nama: string|null}
     *     }>
     * }
     */
    public function getByUuid(string $uuid): array
    {
        $kategori = MasterKategori::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $products = Product::query()
            ->notDeleted()
            ->with(['penjual:uuid,nama_penjual'])
            ->where('uuid_kategori', $uuid)
            ->orderByDesc('created_at')
            ->get();

        return [
            'uuid' => $kategori->uuid,
            'nama_kategori' => $kategori->nama_kategori,
            'total_produk' => $products->count(),
            'produk' => $products
                ->map(fn (Product $product) => $this->formatProduct($product))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     uuid: string,
     *     nama_produk: string,
     *     deskripsi: string,
     *     harga: int,
     *     rating: null,
     *     stock: int,
     *     gambar: string|null,
     *     penjual: array{uuid: string, nama: string|null}
     * }
     */
    private function formatProduct(Product $product): array
    {
        return [
            'uuid' => $product->uuid,
            'nama_produk' => $product->nama_product,
            'deskripsi' => $product->deskripsi,
            'harga' => (int) $product->harga,
            'rating' => null,
            'stock' => (int) $product->stock,
            'gambar' => $product->gambar !== null
                ? Storage::disk('public')->url($product->gambar)
                : null,
            'penjual' => [
                'uuid' => $product->uuid_penjual,
                'nama' => $product->penjual?->nama_penjual,
            ],
        ];
    }
}
