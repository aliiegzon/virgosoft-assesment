<?php

namespace Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Response;
use Tests\BaseFeatureTest;

class UserFeatureTest extends BaseFeatureTest
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * @return void
     */
    public function testStore()
    {
        $data = $this->getCreateOrUpdateData();
        $data['role'] = Role::factory()->create(['guard_name' => 'api'])->name;

        $this->post($this->url, $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure($this->getExpectedJsonResponseStructure(array_diff_key($data, ['role' => '']), true));
    }

    /**
     * @return void
     */
    public function testResendInvitationRoute()
    {
        $model = $this->model->factory()->create(['email_verified_at' => null]);

        $route = "{$this->url}/resend-invitation/{$model->getKey()}";

        $this->post($route)
            ->assertStatus(Response::HTTP_OK);
    }
}
