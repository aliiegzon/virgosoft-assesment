<?php

namespace App\Services;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder;

class BaseService
{
    public int $indexLimit = 25;

    /**
     * @param BaseModel|User|Model $model
     */
    public function __construct(public BaseModel|User|Model $model)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Collection|LengthAwarePaginator|array
    {
        $model = $this->model;

        $query = QueryBuilder::for($model::class)
            ->allowedFilters($model->allowedFilters())
            ->allowedSorts($model->allowedSorts())
            ->defaultSorts($model->defaultSorts())
            ->allowedIncludes($model->allowedIncludes());

        return $query->paginate((int)request()->get('per_page') ?? $this->indexLimit);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(array $data): Model|Collection|QueryBuilder|array|null
    {
        $data = $this->resolveCreateFields($data);

        $this->validateCreate($data);

        $model = $this->model->create($data);

        return $this->show($model->getKey());
    }

    /**
     * @param array $data
     * @return array
     */
    public function resolveCreateFields(array $data): array
    {
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function validateCreate(array $data){

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): Model|Collection|QueryBuilder|array|null
    {
        $model = $this->model;

        return QueryBuilder::for($model::class)
            ->allowedFilters($model->allowedFilters())
            ->allowedIncludes($model->allowedIncludes())
            ->find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id, array $data): Model|Collection|QueryBuilder|array|null
    {
        $model = $this->show($id);

        $data = $this->resolveUpdateFields($data, $model);

        $this->validateUpdate($model, $data);

        $model->update($data);

        return $this->show($id);
    }

    /**
     * @param array $data
     * @param BaseModel|User|Model $model
     * @return array
     */
    public function resolveUpdateFields(array $data, BaseModel|User|Model $model): array
    {
        return $data;
    }

    /**
     * @param BaseModel|User|Model $model
     * @param array $data
     * @return void
     */
    public function validateUpdate(BaseModel|User|Model $model, array $data){

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = $this->show($id);

        $this->validateDelete($model);

        return $model->delete();
    }

    /**
     * @param BaseModel|User|Model $model
     * @return void
     */
    public function validateDelete(BaseModel|User|Model $model){

    }
}
