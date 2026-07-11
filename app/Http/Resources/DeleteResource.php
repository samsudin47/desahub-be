<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Shared\Utilities\SanitizeHelper;

class DeleteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) SanitizeHelper::entityDecode([
            'is_deleted' => 1,
            'updated_at' => dateNow(),
            'updated_by' => getUserId(),
        ]);
    }
}
