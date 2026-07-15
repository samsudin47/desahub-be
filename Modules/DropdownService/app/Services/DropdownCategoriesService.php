<?php

namespace Modules\DropdownService\Services;

use Modules\DataManagement\Services\MasterKategoriService;

class DropdownCategoriesService
{
    public function __construct(private MasterKategoriService $masterKategoriService) {}

    /**
     * @return list<array{uuid: string, nama_kategori: string, deskripsi: string|null}>
     */
    public function getAll(): array
    {
        return $this->masterKategoriService->getAll();
    }
}
