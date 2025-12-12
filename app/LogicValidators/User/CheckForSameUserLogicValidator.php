<?php

namespace App\LogicValidators\User;

use App\LogicValidators\BaseLogicValidator;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CheckForSameUserLogicValidator extends BaseLogicValidator
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
        if ($this->user->is(Auth::user()))
        {
            throw new UnprocessableEntityHttpException("This action cannot be performed on the same user!", code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
