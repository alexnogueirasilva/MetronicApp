<?php declare(strict_types = 1);

namespace App\Models;

use App\Helpers\CnpjHelper;
use Database\Factories\HoldingFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Override;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property string $id
 * @property int $tenant_id
 * @property string $name
 * @property string $legal_name
 * @property string $tax_id
 * @property ?string $email
 * @property ?string $phone
 * @property ?string $description
 * @property ?string $logo_path
 * @property array<string, mixed>|null $settings
 * @property array<string, mixed>|null $address
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ?Carbon $deleted_at
 * @property-read Tenant $tenant
 * @property-read string $formatted_cnpj
 * @property-read ?string $logo_url
 * @property-read ?string $full_address
 *
 * @method static HoldingFactory factory(...$parameters)
 */
#[UseFactory(HoldingFactory::class)]
class Holding extends Model implements Auditable
{
    use HasUlids;
    use HasFactory;
    use Filterable;
    use AuditableTrait;

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $holding): void {
            $holding->tax_id = CnpjHelper::sanitize($holding->tax_id);
        });

        static::updating(static function (self $holding): void {
            if ($holding->isDirty('tax_id')) {
                $holding->tax_id = CnpjHelper::sanitize($holding->tax_id);
            }
        });

        static::deleting(static function (self $holding): void {
            if ($holding->logo_path && Storage::disk('s3')->exists($holding->logo_path)) {
                Storage::disk('s3')->delete($holding->logo_path);
            }
        });
    }

    /**
     * Get the tenant that owns the holding.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get settings value by key.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function canBeDeleted(): bool
    {
        // Placeholder for future logic to determine if the holding can be deleted
        return true;
    }

}
