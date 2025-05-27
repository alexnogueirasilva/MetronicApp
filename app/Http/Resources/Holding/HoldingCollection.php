<?php declare(strict_types = 1);

namespace App\Http\Resources\Holding;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Override;

class HoldingCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->transform(fn ($holding): HoldingResource => new HoldingResource($holding)),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'total_active'   => $this->collection->where('is_active', true)->count(),
                'total_inactive' => $this->collection->where('is_active', false)->count(),
                'with_logo'      => $this->collection->whereNotNull('logo_path')->count(),
                'without_logo'   => $this->collection->whereNull('logo_path')->count(),
            ],
        ];
    }
}
