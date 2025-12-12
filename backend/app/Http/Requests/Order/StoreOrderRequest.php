<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderSide;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbol' => [
                'required',
                'string',
                'max:10'
            ],
            'side' => [
                'required',
                'in:' . OrderSide::getAllValuesAsString()
            ],
            'price' => [
                'required',
                'numeric',
                'gt:0'
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0'
            ],
        ];
    }
}
