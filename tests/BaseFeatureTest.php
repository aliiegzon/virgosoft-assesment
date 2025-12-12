<?php

namespace Tests;

use App\Models\User;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;

abstract class BaseFeatureTest extends TestCase
{
    protected string $url;
    protected $model, $user;

    abstract protected function getModelClass();

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // Create a model instance for testing.
        $this->model = app($this->getModelClass());
        $this->url = '/api/' . $this->model->getTable();
        $this->user = User::factory()->admin()->create();
        Passport::actingAs($this->user);
    }

    /**
     * @param int $count
     * @return mixed
     */
    public function getCreateOrUpdateData(int $count = 1): mixed
    {
        $collection = $this->model->factory($count)->create();
        $data = $collection->toArray();

        if ($count == 1) {
            $model = $collection->first();
            $model->forceDelete();

            $data = $data[0];

            unset($data['id'], $data['created_at'], $data['updated_at']);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param bool $singleResult
     * @return array
     */
    public function getExpectedJsonResponseStructure(array $data, bool $singleResult): array
    {
        $data = $singleResult ? array_keys($data)
            :
            [
                '*' => array_keys($data[0])
            ];

        return [
            'meta' => [
                'code',
                'message',
            ],
            'data' => $data,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $data = $this->getCreateOrUpdateData(2);

        $this->get($this->url)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure($this->getExpectedJsonResponseStructure($data, false));
    }

    /**
     * @return void
     */
    public function testStore()
    {
        $data = $this->getCreateOrUpdateData();

        $this->post($this->url, $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure($this->getExpectedJsonResponseStructure($data, true));
    }

    /**
     * @return void
     */
    public function testShow()
    {
        $model = $this->model->factory()->create();

        $data = $model->toArray();

        $this->get($this->url . '/' . $model->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure($this->getExpectedJsonResponseStructure($data, true));
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $data = $this->getCreateOrUpdateData();
        $model = $this->model->factory()->create();

        $this->put($this->url . '/' . $model->id, $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure($this->getExpectedJsonResponseStructure($data, true));
    }

    /**
     * @return void
     */
    public function testDestroy()
    {
        $model = $this->model->factory()->create();

        $this->delete($this->url . '/' . $model->id)
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }
}
