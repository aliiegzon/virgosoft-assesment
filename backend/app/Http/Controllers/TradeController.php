<?php

namespace App\Http\Controllers;

use App\Http\CustomResponse\CustomResponse;
use App\Http\Resources\Trade\TradeCollection;
use App\Http\Resources\Trade\TradeResource;
use App\Services\TradeService;
use Illuminate\Http\JsonResponse;

class TradeController extends BaseController
{
    /**
     * @param  TradeService  $service
     * @param  CustomResponse  $response
     */
    public function __construct(TradeService $service, public CustomResponse $response)
    {
        parent::__construct($service, TradeResource::class, TradeCollection::class, 'trade');
    }

    /**
     * List trades.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $trades = $this->service->index();

        return $this->customResponse->success(object: new TradeCollection($trades));
    }
}
