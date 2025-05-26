<?php declare(strict_types = 1);

namespace App\Http\Resources\User;

use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

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
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'nickname'          => $this->nickname,
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'email'             => $this->email,
            'avatar'            => $this->url_avatar,
            'email_verified_at' => $this->email_verified_at,
            'otp_method'        => $this->otp_method,
            'totp_verified'     => $this->totp_verified,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'roles'             => $this->whenLoaded('role', function () {
                /** @var Collection<int, Role> $roles */
                $roles = $this->role;

                return $roles->map(fn (Role $role): array => [
                    'id'          => $role->id,
                    'name'        => $role->name,
                    'permissions' => $role->permissions,
                ]);
            }),
        ];
    }
}
