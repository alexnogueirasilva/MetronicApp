<?php declare(strict_types = 1);

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'nickname'          => $this->nickname,
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'email'             => $this->email,
            'avatar'            => $this->avatar,
            'email_verified_at' => $this->email_verified_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'roles'             => $this->whenLoaded('role', fn () => $this->role->map(fn ($role): array => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions,
            ])),
        ];
    }
}
