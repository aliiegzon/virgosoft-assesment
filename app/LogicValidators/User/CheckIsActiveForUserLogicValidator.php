<?php

namespace App\LogicValidators\User;

use App\LogicValidators\BaseLogicValidator;
use App\Models\User;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CheckIsActiveForUserLogicValidator extends BaseLogicValidator
{
    /**
     * @param User $user
     * @param array $data
     */
    public function __construct(public User $user, array $data)
    {
    }

    /**
     * @return void
     */
    function validate(): void
    {
        $user = $this->user;

        if (isset($data['is_active'])) {
            (new CheckForSameUserLogicValidator($user))->validate();

            if ($data['is_active'] === true) {
                if (User::query()->where('email', $user->email)->where('is_active', true)->exists()) {
                    throw new UnprocessableEntityHttpException("There is a user with this email already active!",  code: Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
        }
    }
}
