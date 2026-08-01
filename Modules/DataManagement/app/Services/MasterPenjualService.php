<?php

namespace Modules\DataManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\DataManagement\Models\MasterPenjual;
use Shared\Utilities\PaginationHelper;

class MasterPenjualService
{
    /**
     * @return array{
     *     items: list<array{uuid: string, nama_penjual: string, email: string|null, no_hp: string|null, alamat: string|null}>,
     *     pagination: LengthAwarePaginator
     * }
     */
    public function getPaginated(?int $perPage = null, ?string $search = null): array
    {
        $paginator = MasterPenjual::query()
            ->notDeleted()
            ->when($search !== null, function ($query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('nama_penjual', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('nama_penjual')
            ->paginate(
                PaginationHelper::resolvePerPage($perPage),
                ['uuid', 'nama_penjual', 'email', 'no_hp', 'alamat']
            );

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (MasterPenjual $masterPenjual) => $this->format($masterPenjual))
                ->values()
        );

        return [
            'items' => $paginator->items(),
            'pagination' => $paginator,
        ];
    }

    /**
     * @return list<array{uuid: string, nama_penjual: string, email: string|null, no_hp: string|null, alamat: string|null}>
     */
    public function getAll(): array
    {
        return MasterPenjual::query()
            ->notDeleted()
            ->orderBy('nama_penjual')
            ->get(['uuid', 'nama_penjual', 'email', 'no_hp', 'alamat'])
            ->map(fn (MasterPenjual $masterPenjual) => $this->format($masterPenjual))
            ->values()
            ->all();
    }

    /**
     * @return array{uuid: string, nama_penjual: string, email: string|null, no_hp: string|null, alamat: string|null}
     */
    public function getByUuid(string $uuid): array
    {
        return $this->format(
            MasterPenjual::query()
                ->notDeleted()
                ->where('uuid', $uuid)
                ->firstOrFail()
        );
    }

    /**
     * @param  array{nama_penjual: string, email?: string|null, no_hp?: string|null, alamat?: string|null}  $data
     * @return array{uuid: string, nama_penjual: string, email: string|null, no_hp: string|null, alamat: string|null}
     */
    public function store(array $data): array
    {
        $masterPenjual = MasterPenjual::query()->create([
            'uuid' => generateUuid(),
            'nama_penjual' => $data['nama_penjual'],
            'email' => $data['email'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'is_deleted' => false,
            'created_by' => getUserId(),
        ]);

        return $this->format($masterPenjual);
    }

    /**
     * @param  array{nama_penjual: string, email?: string|null, no_hp?: string|null, alamat?: string|null}  $data
     * @return array{uuid: string, nama_penjual: string, email: string|null, no_hp: string|null, alamat: string|null}
     */
    public function update(string $uuid, array $data): array
    {
        $masterPenjual = MasterPenjual::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $masterPenjual->update([
            'nama_penjual' => $data['nama_penjual'],
            'email' => $data['email'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'updated_by' => getUserId(),
        ]);

        return $this->format($masterPenjual->fresh());
    }

    public function delete(string $uuid): void
    {
        $masterPenjual = MasterPenjual::query()
            ->notDeleted()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $masterPenjual->update([
            ...resourceData()->delete($masterPenjual),
            'deleted_by' => getUserId(),
        ]);
    }

    /**
     * @return array{uuid: string, nama_penjual: string, email: string|null, no_hp: string|null, alamat: string|null}
     */
    private function format(MasterPenjual $masterPenjual): array
    {
        return [
            'uuid' => $masterPenjual->uuid,
            'nama_penjual' => $masterPenjual->nama_penjual,
            'email' => $masterPenjual->email,
            'no_hp' => $masterPenjual->no_hp,
            'alamat' => $masterPenjual->alamat,
        ];
    }
}
