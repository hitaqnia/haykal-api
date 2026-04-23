<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Response;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * Wraps a paginator into the Haykal envelope's `data` payload.
 *
 * Output shape:
 *
 *     {
 *         "items": [...],
 *         "pagination": {
 *             "page": 1,
 *             "per_page": 15,
 *             "total": 42
 *         }
 *     }
 *
 * `last_page` and `from`/`to` metadata are intentionally omitted —
 * clients can derive `last_page` from `total` and `per_page`, and the
 * slimmer payload reduces noise in JSON logs.
 */
final class PaginatedResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->resource;

        $payload = $paginator->toArray();
        $meta = Arr::only($payload, ['current_page', 'per_page', 'total']);

        return [
            'items' => $payload['data'] ?? [],
            'pagination' => [
                'page' => $meta['current_page'] ?? null,
                'per_page' => $meta['per_page'] ?? null,
                'total' => $meta['total'] ?? null,
            ],
        ];
    }
}
