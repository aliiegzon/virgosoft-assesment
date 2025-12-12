<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Traits\ModelQueryBuilderTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes, ModelQueryBuilderTrait, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'is_active',
        'balance',
        'email_verified_at',
        'created_by_id',
        'updated_by_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'balance'  => 'decimal:8',
    ];

    protected $appends = [
        'full_name'
    ];

    /**
     * @return string[]
     */
    public function allowedIncludes(): array
    {
        return [
            'roles',
            'roles.permissions',
            'orders',
            'openOrders',
            'assets',
        ];
    }

    /**
     * @return array
     */
    public function allowedFilters(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'is_active',
        ];
    }

    /**
     * @return array
     */
    public function allowedSorts(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'is_active',
            'created_at',
        ];
    }

    /**
     * @return array
     */
    public function defaultSorts(): array
    {
        return ['-created_at'];
    }

    /**
     * @return string|null
     */
    public function getFullNameAttribute(): ?string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return HasMany
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany
     */
    public function openOrders(): HasMany
    {
        return $this->hasMany(Order::class)
            ->where('status', OrderStatus::OPEN)
            ->orderBy('created_at');
    }
}
