<?php

namespace App\Http\Resources\Trade;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TradeCollection extends ResourceCollection
{
    public $collects = TradeResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<int, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
