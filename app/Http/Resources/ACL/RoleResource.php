<?php declare(strict_types = 1);

namespace App\Http\Resources\ACL;

use App\Models\Auth\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'icon'         => $this->icon,
            'icon_class'   => $this->icon_class,
            'fill_class'   => $this->fill_class,
            'stroke_class' => $this->stroke_class,
            'size_class'   => $this->size_class,
            'description'  => $this->description,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'count_users'  => $this->count_users,
            'permissions'  => $this->groupedPermissions(),
        ];
    }
}
