<?php
declare(strict_types = 1);

namespace App\Http\Resources\ACL;

use App\Models\Auth\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Override;

/**
 * Resource for grouped permissions
 */
class GroupedPermissionResource extends JsonResource
{
    /**
     * @return array<string, array<int, array{id: string, name: string, icon: string|null, description: string|null}>>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Collection<int, Permission> $permissions */
        $permissions = $this->resource;

        /** @var Collection<string, Collection<int, Permission>> $grouped */
        $grouped = $permissions->groupBy(fn (Permission $permission): string => explode(':', (string) $permission->name)[0]);

        /** @var array<string, array<int, array{id: string, name: string, icon: string|null, description: string|null}>> $result */
        $result = $grouped->map(fn (Collection $group): array => $group->map(fn (Permission $permission): array => [
            'id'          => (string)$permission->id,
            'name'        => explode(':', (string) $permission->name)[1] ?? (string)$permission->name,
            'icon'        => $permission->icon,
            'description' => $permission->description,
        ])->values()->toArray())->toArray();

        return $result;
    }
}
