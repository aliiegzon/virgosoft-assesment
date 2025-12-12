<?php

namespace App\LogicValidators\User;

use App\LogicValidators\BaseLogicValidator;
use App\Models\User;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CheckIfUserVerifiedLogicValidator extends BaseLogicValidator
{
    /**
     * @param User $user
     */
    public function __construct(public User $user)
    {
    }

    /**
     * @return void
     */
    function validate(): void
    {
        if (!is_null($this->user->email_verified_at)) {
            throw new UnprocessableEntityHttpException("The user is already verified!", code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
