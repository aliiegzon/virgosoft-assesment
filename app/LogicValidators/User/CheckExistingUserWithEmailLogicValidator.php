<?php

namespace App\LogicValidators\User;

use App\LogicValidators\BaseLogicValidator;
use App\Models\User;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CheckExistingUserWithEmailLogicValidator extends BaseLogicValidator
{
    /**
     * @param string $email
     */
    public function __construct(public string $email)
    {
    }

    /**
     * @return void
     */
    function validate(): void
    {
        $email = $this->email;

        $existingUserWithEmail = User::query()->select('email')->where('email', $email)->where('is_active', true)->first();
        if ($existingUserWithEmail) {
            if ($email === $existingUserWithEmail->email) {
                throw new UnprocessableEntityHttpException("There is a user with this email already!",  code: Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
    }
}
