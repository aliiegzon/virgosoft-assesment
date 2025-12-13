<?php

namespace App\Services;

use App\Models\Trade;

class TradeService extends BaseService
{
    /**
     * @param  Trade  $model
     */
    public function __construct(Trade $model)
    {
        parent::__construct($model);
        $this->indexLimit = 50;
    }
}
