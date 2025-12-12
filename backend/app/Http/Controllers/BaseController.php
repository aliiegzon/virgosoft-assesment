<?php

namespace App\Http\Controllers;

use App\Http\CustomResponse\CustomResponse;
use App\Models\BaseModel;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected CustomResponse $customResponse;

    /**
     * @param  BaseService  $service
     * @param  string  $resource
     * @param  string  $collection
     * @param  string  $authParam
     * @param  bool  $authorizeResource
     */
    public function __construct(public BaseService $service, public string $resource, public string $collection, public string $authParam, public bool $authorizeResource = true)
    {
        $this->customResponse = new CustomResponse();

        if ($authorizeResource) {
            $this->authorizeResource($this->service->model::class, $this->authParam);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function indexHelp()
    {
        $modelData = $this->service->index();
        $modelCollection = new $this->collection($modelData);

        return $this->customResponse->success(object: $modelCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeHelp(Request $request)
    {
        $modelData = $this->service->store($request->validated());
        $modelResource = new $this->resource($modelData);

        return $this->customResponse->created(object: $modelResource);
    }

    /**
     * Display the specified resource.
     */
    public function showHelp(BaseModel|Model $model)
    {
        $modelData = $this->service->show($model->getKey());
        $modelResource = new $this->resource($modelData);

        return $this->customResponse->success(object: $modelResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateHelp(Request $request, BaseModel|Model $model)
    {
        $modelData = $this->service->update($model->getKey(), $request->validated());
        $modelResource = new $this->resource($modelData);

        return $this->customResponse->success(object: $modelResource);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyHelp(BaseModel|Model $model)
    {
        $this->service->destroy($model->getKey());

        return $this->customResponse->noContent();
    }
}
