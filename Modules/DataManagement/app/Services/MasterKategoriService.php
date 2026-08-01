<?php

namespace Modules\DataManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\DataManagement\Models\MasterKategori;
use Shared\Utilities\PaginationHelper;

class MasterKategoriService
{
    /**
     * @return array{
     *     items: list<array{uuid: string, nama_kategori: string, deskripsi: string|null}>,
     *     pagination: LengthAwarePaginator
     * }
     */
    public function getPaginated(?int $perPage = null, ?string $search = null): array
    {
        $paginator = MasterKategori::query()
            ->notDeleted()
            ->when($search !== null, function ($query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('nama_kategori', 'like', $term)
                        ->orWhere('deskripsi', 'like', $term);
                });
            })
            ->orderBy('nama_kategori')
            ->paginate(
                PaginationHelper::resolvePerPage($perPage),
                ['uuid', 'nama_kategori', 'deskripsi']
            );

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (MasterKategori $masterKategori) => $this->format($masterKategori))
                ->values()
        );

        return [
            'items' => $paginator->items(),
            'pagination' => $paginator,
        ];
    }

    /**
     * @return list<array{uuid: string, nama_kategori: string, deskripsi: string|null}>
     */
    public function getAll(): array
    {
        return MasterKategori::query()
            ->notDeleted()
            ->orderBy('nama_kategori')
            ->get(['uuid', 'nama_kategori', 'deskripsi'])
            ->map(fn (MasterKategori $masterKategori) => $this->format($masterKategori))
            ->values()
            ->all();
    }

    /**
     * @return array{uuid: string, nama_kategori: string, deskripsi: string|null}
     */
    public function getByUuid(string $uuid): array
    {
        return $this->format(
            MasterKategori::query()
                ->notDeleted()
                ->where('uuid', $uuid)
                ->firstOrFail()
        );
    }

    /**
     * @param  array{nama_kategori: string, deskripsi?: string|null}  $data
     * @return array{uuid: string, nama_kategori: string, deskripsi: string|null}
     */
    public function store(array $data): array
    {
        $masterKategori = MasterKategori::query()->create([
            'uuid' => generateUuid(),
            'nama_kategori' => $data['nama_kategori'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'is_deleted' => false,
            'created_by' => getUserId(),
        ]);

        return $this->format($masterKategori);
    }

    /**
     * @param  array{nama_kategori: string, deskripsi?: string|null}  $data
     * @return array{uuid: string, nama_kategori: string, deskripsi: string|null}
     */
    public function update(string $uuid, array $data): array
    {
        $masterKategori = MasterKategori::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $masterKategori->update([
            'nama_kategori' => $data['nama_kategori'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'updated_by' => getUserId(),
        ]);

        return $this->format($masterKategori->fresh());
    }

    public function delete(string $uuid): void
    {
        $masterKategori = MasterKategori::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $masterKategori->update([
            ...resourceData()->delete($masterKategori),
            'deleted_by' => getUserId(),
        ]);
    }

    /**
     * @return array{uuid: string, nama_kategori: string, deskripsi: string|null}
     */
    private function format(MasterKategori $masterKategori): array
    {
        return [
            'uuid' => $masterKategori->uuid,
            'nama_kategori' => $masterKategori->nama_kategori,
            'deskripsi' => $masterKategori->deskripsi,
        ];
    }
}
