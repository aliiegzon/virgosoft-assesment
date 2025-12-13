<?php

namespace App\Http\Resources\Trade;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'symbol'      => $this->symbol,
            'buy_order_id'  => $this->buy_order_id,
            'sell_order_id' => $this->sell_order_id,
            'price'       => $this->price,
            'amount'      => $this->amount,
            'volume_usd'  => $this->volume_usd,
            'fee_usd'     => $this->fee_usd,
            'created_at'  => $this->created_at,
        ];
    }
}
