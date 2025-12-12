<?php

namespace App\Models;

use App\Traits\ModelQueryBuilderTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
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
}
