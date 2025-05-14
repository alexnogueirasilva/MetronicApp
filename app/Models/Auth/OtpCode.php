<?php declare(strict_types = 1);

namespace App\Models\Auth;

use App\Enums\TypeOtp;
use Database\Factories\Auth\OtpCodeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $email
 * @property TypeOtp $code
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property bool $used
 */
class OtpCode extends Model
{
    /** @use HasFactory<OtpCodeFactory> */
    use HasFactory;
    use HasUlids;

    protected $guarded = [];

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    protected function casts(): array
    {
        return [
            'used'       => 'boolean',
            'expires_at' => 'datetime',
            'type'       => TypeOtp::class,
        ];
    }
}
