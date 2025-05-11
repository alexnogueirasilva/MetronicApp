<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Auth\{Role};
use App\Models\Traits\HasRole;
use Database\Factories\UserFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\{Carbon};
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $avatar
 * @property string $password
 * @property ?string $remember_token
 * @property ?Carbon $email_verified_at
 * @property ?string $totp_secret
 * @property ?string $otp_method
 * @property bool $totp_verified
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasRole;
    use Filterable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function role(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

}
