<?php

namespace Modules\DataManagement\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesMasterPenjual
{
    /**
     * @return array<string, mixed>
     */
    protected function masterPenjualRules(?string $uuid = null): array
    {
        $uniqueNamaPenjual = Rule::unique('master_penjual', 'nama_penjual')
            ->where(fn ($query) => $query->where('is_deleted', false));

        $uniqueEmail = Rule::unique('master_penjual', 'email')
            ->where(fn ($query) => $query->where('is_deleted', false));

        if ($uuid !== null) {
            $uniqueNamaPenjual = $uniqueNamaPenjual->ignore($uuid, 'uuid');
            $uniqueEmail = $uniqueEmail->ignore($uuid, 'uuid');
        }

        return [
            'nama_penjual' => ['required', 'string', 'max:255', $uniqueNamaPenjual],
            'email' => ['nullable', 'email', 'max:255', $uniqueEmail],
            'no_hp' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
