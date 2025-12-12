<?php

namespace LogicValidators;

use App\LogicValidators\User\CheckIsActiveForUserLogicValidator;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CheckIsActiveForUserLogicValidatorTest extends TestCase
{
    /**
     * @param Closure $userAuthUserAndData
     * @param string|null $expectedException
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(Closure $userAuthUserAndData, ?string $expectedException): void
    {
        Carbon::setTestNow(now());

        [$user, $data, $authUser] = $userAuthUserAndData();

        Passport::actingAs($authUser);

        if ($expectedException) {
            $this->expectException($expectedException);
        } else {
            $this->expectNotToPerformAssertions();
        }

        (new CheckIsActiveForUserLogicValidator($user, $data))->validate();
    }

    /**
     * @return array[]
     */
    public static function validateDataProvider(): array
    {
        return [
            'Scenario 1: Data is active not set' =>[
                'userAuthUserAndData'             => function () {
                    $user = User::factory()->create();
                    $data = [];

                    return [
                        $user,
                        $data,
                        $user,
                    ];
                },
                'expectedException' => null,
            ],
            'Scenario 2: Authenticated user is not the same as user and data is active set as false' =>[
                'userAuthUserAndData'             => function () {
                    $user = User::factory()->create();
                    $authUser = User::factory()->create();
                    $data = [
                        'is_active' => false
                    ];

                    return [
                        $user,
                        $data,
                        $authUser
                    ];
                },
                'expectedException' => null,
            ],
            'Scenario 3: Authenticated user is not the same as user, data is active set as true and user is_active is false' =>[
                'userAuthUserAndData'             => function () {
                    $user = User::factory()->create([
                        'is_active' => false,
                        'email_verified_at' => null
                    ]);
                    $authUser = User::factory()->create();
                    $data = [
                        'is_active' => true
                    ];

                    return [
                        $user,
                        $data,
                        $authUser
                    ];
                },
                'expectedException' => null,
            ]
        ];
    }
}
