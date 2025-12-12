<?php

namespace App\Traits;

trait ModelQueryBuilderTrait
{
    /**
     * @return array
     */
    public function allowedFilters(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function allowedSorts(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function defaultSorts(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function allowedIncludes(): array
    {
        return [];
    }
}
