<?php

namespace LogicValidators;

use App\LogicValidators\User\CheckExistingUserWithEmailLogicValidator;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class CheckExistingUserWithEmailLogicValidatorTest extends TestCase
{
    /**
     * @param Closure $email
     * @param string|null $expectedException
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(Closure $email, ?string $expectedException): void
    {
        Carbon::setTestNow(now());

        $email = $email();

        if ($expectedException) {
            $this->expectException($expectedException);
        } else {
            $this->expectNotToPerformAssertions();
        }

        (new CheckExistingUserWithEmailLogicValidator($email))->validate();
    }

    /**
     * @return array[]
     */
    public static function validateDataProvider(): array
    {
        return [
            'Scenario 1: User exists with this email' =>[
                'email'             => function () {
                    $user = User::factory()->create([
                        'is_active' => true
                    ]);

                    return $user->email;
                },
                'expectedException' => UnprocessableEntityHttpException::class,
            ],
            'Scenario 2: User does not exist with this email' =>[
                'user'             => function () {
                    User::factory()->create([
                        'is_active' => true
                    ]);

                    return 'admin@hmdtrucking.com';
                },
                'expectedException' => null,
            ]
        ];
    }
}
