<?php

namespace Tests;

abstract class BaseServiceTest extends TestCase
{
    protected $service, $model;

    abstract protected function getModelClass();

    abstract protected function getServiceClass();

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->model = app($this->getModelClass());
        $this->service = app($this->getServiceClass());
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $expectedIds = $this->model->factory(2)->create()->modelKeys();

        $actualIds = $this->service->index()->modelKeys();

        $this->assertEquals($expectedIds, $actualIds);
    }

    /**
     * @return void
     */
    public function testShow()
    {
        $model = $this->model->factory()->create();
        $fetchedModel = $this->service->show($model->getKey());

        $this->assertEquals($model->getKey(), $fetchedModel->getKey());
    }

    /**
     * @return void
     */
    public function testStore()
    {
        $model = $this->model->factory()->create();
        $createData = $model->toArray();
        $model->forceDelete();

        $createdModel = $this->service->store($createData);

        $this->assertDatabaseHas($this->model->getTable(), ['id' => $createdModel->getKey()]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $model = $this->model->factory()->create();
        $updateData = $model->toArray();
        $model->forceDelete();

        unset($updateData['id'], $updateData['created_at'], $updateData['updated_at']);

        $model = $this->model->factory()->create();

        $this->service->update($model->getKey(), $updateData);

        $updateData['id'] = $model->getKey();

        $this->assertDatabaseHas($model->getTable(), $updateData);
    }

    /**
     * @return void
     */
    public function testDestroy()
    {
        $model = $this->model->factory()->create();

        $this->service->destroy($model->getKey());

        $this->assertDatabaseMissing($model->getTable(), ['id' => $model->getKey()]);
    }
}
