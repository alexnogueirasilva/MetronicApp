<?php declare(strict_types = 1);

namespace App\Models\Auth;

use Database\Factories\Auth\PermissionFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;
    use HasUlids;
    use Filterable;
}
