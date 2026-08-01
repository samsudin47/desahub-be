<?php

namespace Modules\MarketplaceService\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\MarketplaceService\Models\Product;
use Shared\Utilities\PaginationHelper;

class ProductService
{
    /**
     * @return array{
     *     items: list<array{
     *         uuid: string,
     *         nama_product: string,
     *         deskripsi: string,
     *         harga: int,
     *         stock: int,
     *         gambar: string|null,
     *         uuid_kategori: string,
     *         nama_kategori: string|null,
     *         uuid_penjual: string,
     *         nama_penjual: string|null
     *     }>,
     *     pagination: LengthAwarePaginator
     * }
     */
    public function getPaginated(?int $perPage = null, ?string $search = null): array
    {
        $paginator = Product::query()
            ->notDeleted()
            ->with([
                'kategori:uuid,nama_kategori',
                'penjual:uuid,nama_penjual',
            ])
            ->when($search !== null, function ($query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('nama_product', 'like', $term)
                        ->orWhere('deskripsi', 'like', $term)
                        ->orWhereHas('kategori', function ($query) use ($term): void {
                            $query->where('nama_kategori', 'like', $term);
                        })
                        ->orWhereHas('penjual', function ($query) use ($term): void {
                            $query->where('nama_penjual', 'like', $term);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(PaginationHelper::resolvePerPage($perPage));

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (Product $product) => $this->format($product))
                ->values()
        );

        return [
            'items' => $paginator->items(),
            'pagination' => $paginator,
        ];
    }

    /**
     * @return list<array{
     *     uuid: string,
     *     nama_product: string,
     *     deskripsi: string,
     *     harga: int,
     *     stock: int,
     *     gambar: string|null,
     *     uuid_kategori: string,
     *     nama_kategori: string|null,
     *     uuid_penjual: string,
     *     nama_penjual: string|null
     * }>
     */
    public function getAll(): array
    {
        return Product::query()
            ->notDeleted()
            ->with([
                'kategori:uuid,nama_kategori',
                'penjual:uuid,nama_penjual',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Product $product) => $this->format($product))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     uuid: string,
     *     nama_product: string,
     *     deskripsi: string,
     *     harga: int,
     *     stock: int,
     *     gambar: string|null,
     *     uuid_kategori: string,
     *     nama_kategori: string|null,
     *     uuid_penjual: string,
     *     nama_penjual: string|null
     * }
     */
    public function getByUuid(string $uuid): array
    {
        return $this->format(
            Product::query()
                ->notDeleted()
                ->with([
                    'kategori:uuid,nama_kategori',
                    'penjual:uuid,nama_penjual',
                ])
                ->where('uuid', $uuid)
                ->firstOrFail()
        );
    }

    /**
     * @param  array{
     *     nama_product: string,
     *     deskripsi: string,
     *     harga: int,
     *     stock: int,
     *     uuid_kategori: string,
     *     uuid_penjual: string,
     *     gambar: UploadedFile
     * }  $data
     * @return array{
     *     uuid: string,
     *     nama_product: string,
     *     deskripsi: string,
     *     harga: int,
     *     stock: int,
     *     gambar: string|null,
     *     uuid_kategori: string,
     *     nama_kategori: string|null,
     *     uuid_penjual: string,
     *     nama_penjual: string|null
     * }
     */
    public function store(array $data): array
    {
        $gambarPath = $this->storeGambar($data['gambar']);

        $product = Product::query()->create([
            'uuid' => generateUuid(),
            'nama_product' => $data['nama_product'],
            'deskripsi' => $data['deskripsi'],
            'harga' => $data['harga'],
            'stock' => $data['stock'],
            'gambar' => $gambarPath,
            'uuid_kategori' => $data['uuid_kategori'],
            'uuid_penjual' => $data['uuid_penjual'],
            'is_deleted' => false,
            'created_by' => getUserId(),
        ]);

        return $this->format(
            $product->load([
                'kategori:uuid,nama_kategori',
                'penjual:uuid,nama_penjual',
            ])
        );
    }

    /**
     * @param  array{
     *     nama_product: string,
     *     deskripsi: string,
     *     harga: int,
     *     stock: int,
     *     uuid_kategori: string,
     *     uuid_penjual: string,
     *     gambar?: UploadedFile|null
     * }  $data
     * @return array{
     *     uuid: string,
     *     nama_product: string,
     *     deskripsi: string,
     *     harga: int,
     *     stock: int,
     *     gambar: string|null,
     *     uuid_kategori: string,
     *     nama_kategori: string|null,
     *     uuid_penjual: string,
     *     nama_penjual: string|null
     * }
     */
    public function update(string $uuid, array $data): array
    {
        $product = Product::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $payload = [
            'nama_product' => $data['nama_product'],
            'deskripsi' => $data['deskripsi'],
            'harga' => $data['harga'],
            'stock' => $data['stock'],
            'uuid_kategori' => $data['uuid_kategori'],
            'uuid_penjual' => $data['uuid_penjual'],
            'updated_by' => getUserId(),
        ];

        if (isset($data['gambar']) && $data['gambar'] instanceof UploadedFile) {
            $this->deleteGambar($product->gambar);
            $payload['gambar'] = $this->storeGambar($data['gambar']);
        }

        $product->update($payload);

        return $this->format(
            $product->fresh()->load([
                'kategori:uuid,nama_kategori',
                'penjual:uuid,nama_penjual',
            ])
        );
    }

    public function delete(string $uuid): void
    {
        $product = Product::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $product->update([
            ...resourceData()->delete($product),
            'deleted_by' => getUserId(),
        ]);
    }

    private function storeGambar(UploadedFile $gambar): string
    {
        return $gambar->store('products', 'public');
    }

    private function deleteGambar(?string $path): void
    {
        if ($path !== null && $path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array{
     *     uuid: string,
     *     nama_product: string,
     *     deskripsi: string,
     *     harga: int,
     *     stock: int,
     *     gambar: string|null,
     *     uuid_kategori: string,
     *     nama_kategori: string|null,
     *     uuid_penjual: string,
     *     nama_penjual: string|null
     * }
     */
    private function format(Product $product): array
    {
        return [
            'uuid' => $product->uuid,
            'nama_product' => $product->nama_product,
            'deskripsi' => $product->deskripsi,
            'harga' => (int) $product->harga,
            'stock' => (int) $product->stock,
            'gambar' => $product->gambar !== null ? Storage::disk('public')->url($product->gambar) : null,
            'uuid_kategori' => $product->uuid_kategori,
            'nama_kategori' => $product->kategori?->nama_kategori,
            'uuid_penjual' => $product->uuid_penjual,
            'nama_penjual' => $product->penjual?->nama_penjual,
        ];
    }
}
