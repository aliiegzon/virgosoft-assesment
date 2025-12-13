<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderSide;
use App\Enums\OrderSymbol;
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
                'in:' . OrderSymbol::getAllValuesAsString()
            ],
            'side' => [
                'required',
                'in:' . OrderSide::getAllValuesAsString()
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0'
            ],
            'price' => [
                'prohibited'
            ],
        ];
    }
}
