<?php declare(strict_types = 1);

namespace App\Models\Finance;

use Database\Factories\Finance\AccountFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property string $type
 * @property string $currency
 * @property string $balance
 * @property string $created_at
 * @property string $updated_at
 * @property string $tenant_id
 * @property string $created_by
 */
#[UseFactory(AccountFactory::class)]
class Account extends Model
{
    use HasFactory;
    use HasUlids;
    use Filterable;
}
