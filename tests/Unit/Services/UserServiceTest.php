<?php

namespace Services;

use App\Models\Role;
use App\Models\User;
use App\Notifications\UserCreatedNotification;
use App\Services\UserService;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\BaseServiceTest;

class UserServiceTest extends BaseServiceTest
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->model = new User();
        $this->service = new UserService($this->model);
    }

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * @return string
     */
    protected function getServiceClass(): string
    {
        return UserService::class;
    }

    /**
     * @return void
     */
    public function testStore()
    {
        $model = $this->model->factory()->create();
        $createData = $model->toArray();
        $model->forceDelete();

        $createData['role'] = Role::factory()->create([
            'guard_name' => 'web'
        ])->name;

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

        unset($updateData['id'], $updateData['created_at'], $updateData['updated_at'], $updateData['full_name']);

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

        $authUser = $this->model->factory()->create();
        $this->actingAs($authUser);

        $this->service->destroy($model->getKey());

        $this->assertSoftDeleted($model);
    }

    /**
     * @return void
     */
    public function testResendInvitation()
    {
        Notification::fake();

        $model = $this->model->factory()->create(['email_verified_at' => null]);
        $this->service->resendInvitation($model);

        Notification::assertSentTo(
            [$model],
            UserCreatedNotification::class
        );

        $verifiedUser = $this->model->factory()->create();

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->service->resendInvitation($verifiedUser);
    }
}
