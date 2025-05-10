<?php declare(strict_types = 1);

namespace App\Models\Auth;

use Database\Factories\Auth\RoleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property ?string $icon
 * @property ?string $description
 * @property bool $is_default
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;
    use HasUlids;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
