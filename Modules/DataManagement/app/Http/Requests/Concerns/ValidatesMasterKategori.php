<?php

namespace Modules\DataManagement\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesMasterKategori
{
    /**
     * @return array<string, mixed>
     */
    protected function masterKategoriRules(?string $uuid = null): array
    {
        $uniqueNamaKategori = Rule::unique('master_kategori', 'nama_kategori')
            ->where(fn ($query) => $query->where('is_deleted', false));

        if ($uuid !== null) {
            $uniqueNamaKategori = $uniqueNamaKategori->ignore($uuid, 'uuid');
        }

        return [
            'nama_kategori' => ['required', 'string', 'max:255', $uniqueNamaKategori],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
