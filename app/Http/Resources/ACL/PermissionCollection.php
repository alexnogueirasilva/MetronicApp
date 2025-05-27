<?php declare(strict_types = 1);

namespace App\Http\Resources\ACL;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Override;

class PermissionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->transform(fn ($permission): PermissionResource => new PermissionResource($permission)),
        ];
    }
}
