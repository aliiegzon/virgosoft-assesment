<?php

namespace LogicValidators;

use App\LogicValidators\User\CheckForSameUserLogicValidator;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Laravel\Passport\Passport;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class CheckForSameUserLogicValidatorTest extends TestCase
{
    /**
     * @param Closure $user
     * @param string|null $expectedException
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(Closure $user, ?string $expectedException): void
    {
        Carbon::setTestNow(now());

        $user = $user();

        Passport::actingAs($user);

        if ($expectedException) {
            $this->expectException($expectedException);
        } else {
            $this->expectNotToPerformAssertions();
        }

        (new CheckForSameUserLogicValidator($user))->validate();
    }

    /**
     * @return array[]
     */
    public static function validateDataProvider(): array
    {
        return [
            'Scenario 1: Authenticated user is the same as the user' =>[
                'user'             => function () {
                    return User::factory()->create();
                },
                'expectedException' => UnprocessableEntityHttpException::class,
            ]
        ];
    }
}
