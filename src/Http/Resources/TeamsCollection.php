<?php

declare(strict_types=1);

namespace Litepie\Teams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * TeamsCollection
 * 
 * API resource collection for teams.
 */
class TeamsCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     */
    public $collects = TeamResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
                'path' => $this->resource->path(),
                'next_page_url' => $this->resource->nextPageUrl(),
                'prev_page_url' => $this->resource->previousPageUrl(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'status' => 'success',
            'timestamp' => now()->toISOString(),
        ];
    }
}
