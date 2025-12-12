<?php

namespace App\Http\Controllers;

use App\Http\CustomResponse\CustomResponse;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends BaseController
{
    /**
     * @param  OrderService  $service
     * @param  CustomResponse  $response
     */
    public function __construct(OrderService $service, public CustomResponse $response)
    {
        parent::__construct($service, OrderResource::class, OrderCollection::class, 'order');
    }

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $orders = $this->service->index();

        return $this->customResponse->success(object: new OrderCollection($orders));
    }

    /**
     * @param  StoreOrderRequest  $request
     * @return JsonResponse
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        [$order, $trade] = $this->service->placeOrder($request->user(), $request->validated());

        $data = [
            'order' => new OrderResource($order),
        ];

        if ($trade) {
            $data['trade'] = $trade;
        }

        return $this->customResponse->created(object: $data);
    }

    /**
     * @param  Request  $request
     * @param  Order  $order
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $order = $this->service->cancel($request->user(), $order);

        return $this->customResponse->success(object: new OrderResource($order));
    }
}
