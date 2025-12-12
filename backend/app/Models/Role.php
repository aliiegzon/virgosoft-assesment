<?php

namespace App\Models;

use App\Traits\ModelQueryBuilderTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory, HasUuids, ModelQueryBuilderTrait;

    protected $fillable = [
        'name',
        'guard_name'
    ];

    protected $hidden = [
        'guard_name',
        'created_at',
        'updated_at',
        'pivot'
    ];

    /**
     * @return string[]
     */
    public function allowedIncludes(): array
    {
        return [
            'permissions'
        ];
    }
}
