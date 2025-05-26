<?php declare(strict_types = 1);

namespace App\Http\Resources\ACL;

use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

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
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'icon'        => $this->icon,
            'description' => $this->description,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'count_users' => $this->count_users,
            'users'       => $this->whenLoaded('users', function () {
                /** @var Collection<int, User> $users */
                $users = $this->users;

                return $users->map(fn (User $user): array => [
                    'id'         => $user->id,
                    'nickname'   => $user->nickname,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'avatar'     => $user->urlAvatar(),
                ]);
            }),
            'permissions' => $this->groupedPermissions(),
        ];
    }
}
