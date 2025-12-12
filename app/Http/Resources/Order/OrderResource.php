<?php

namespace App\Http\Resources\Order;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof OrderStatus ? $this->status : OrderStatus::from((int)$this->status);

        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'symbol'       => $this->symbol,
            'side'         => $this->side,
            'price'        => $this->price,
            'amount'       => $this->amount,
            'status'       => [
                'value' => $status->value,
                'label' => $status->name,
            ],
            'locked_value' => $this->locked_value,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
