<?php

namespace Shared\Utilities;

use App\Http\Resources\DeleteResource;

class DeleteResourceHelper
{
    public function delete($resource = null)
    {
        $data = new DeleteResource($resource);
        return $data->resolve();
    }
}
