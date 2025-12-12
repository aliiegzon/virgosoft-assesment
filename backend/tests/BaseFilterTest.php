<?php

namespace Tests;

use App\Models\User;
use Laravel\Passport\Passport;

abstract class BaseFilterTest extends TestCase
{
    public User $user;
    public string $endpoint;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->admin()->create();
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    protected function testBulkFilters(array $filters = [], array $expectedIds = []): void
    {
        $path = $this->resolveFilterUrl($filters);

        Passport::actingAs($this->user);

        $response = $this->get($path);

        $data = $response->json()['data'];

        $actualIds = array_column($data, 'id');

        sort($actualIds);

        $this->assertSame($expectedIds, $actualIds);
    }

    /**
     * @param array $filters
     * @return string
     */
    public function resolveFilterUrl(array $filters): string
    {
        $path = '/api/' . $this->endpoint;

        if (empty($filters)) {
            return $path;
        }

        $path .= '?';

        foreach ($filters as $key => $value) {
            if(is_bool($value)){
                $value = $value ? 'true' : 'false';
            }

            $path .= "filter[{$key}]={$value}&";
        }

        return rtrim($path, "&");
    }
}
