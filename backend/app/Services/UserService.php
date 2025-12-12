<?php

namespace App\Services;

use App\LogicValidators\User\CheckExistingUserWithEmailLogicValidator;
use App\LogicValidators\User\CheckForSameUserLogicValidator;
use App\LogicValidators\User\CheckIfUserVerifiedLogicValidator;
use App\LogicValidators\User\CheckIsActiveForUserLogicValidator;
use App\Models\BaseModel;
use App\Models\User;
use App\Notifications\UserCreatedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

class UserService extends BaseService
{
    /**
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array $data
     * @return Model|Collection|QueryBuilder|array|null
     */
    public function store(array $data): Model|Collection|QueryBuilder|array|null
    {
        (new CheckExistingUserWithEmailLogicValidator($data['email']))->validate();

        $data = $this->resolveCreateFields($data);

        $user = $this->model->create($data);
        $user->assignRole($data['role']);

        $token = $this->createToken($data['email']);
        $user->notify(new UserCreatedNotification($token));

        return $this->show($user->getKey());
    }

    /**
     * @param string $id
     * @param array $data
     * @return Model|Collection|QueryBuilder|array|null
     */
    public function update(string $id, array $data): Model|Collection|QueryBuilder|array|null
    {
        $user = $this->show($id);

        (new CheckIsActiveForUserLogicValidator($user, $data))->validate();

        $emailChanged = Arr::has($data, 'email') && $user->email !== $data['email'];

        $data = $this->resolveUpdateFields($data, $user);

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles($data['role']);
        }

        if ($emailChanged && is_null($user->email_verified_at)) {
            $token = $this->createToken($data['email']);
            $user->notify(new UserCreatedNotification($token));
        }

        return $this->show($id);
    }

    /**
     * @param string $id
     * @return bool|mixed|null
     */
    public function destroy(string $id)
    {
        $user = $this->show($id);

        (new CheckForSameUserLogicValidator($user))->validate();

        return $user->delete();
    }

    /**
     * @param User $user
     * @return void
     */
    public function resendInvitation(User $user): void
    {
        (new CheckIfUserVerifiedLogicValidator($user))->validate();

        DB::table('password_set_tokens')->where('email', $user->email)->delete();
        $token = $this->createToken($user->email);

        $user->notify(new UserCreatedNotification($token));
    }

    /**
     * @param array $data
     * @return array
     */
    public function resolveCreateFields(array $data): array
    {
        $data['created_by_id'] = Auth::id();

        return $data;
    }

    public function resolveUpdateFields(array $data, Model|User|BaseModel $model): array
    {
        $data['updated_by_id'] = Auth::id();

        return $data;
    }

    /**
     * @param string $email
     * @return string
     */
    public function createToken(string $email): string
    {
        $token = Str::random(60);
        DB::table('password_set_tokens')->insert([
            'email'      => $email,
            'token'      => bcrypt($token),
            'created_at' => now()
        ]);

        return $token;
    }
}
