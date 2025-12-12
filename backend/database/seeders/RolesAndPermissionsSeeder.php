<?php

namespace Database\Seeders;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedPermissionsForAdminRole();
    }

    /**
     * @return Model|Builder
     */
    public function seedAdminRole(): Model|Builder
    {
        return Role::query()->firstOrCreate([
            'name' => UserRole::ADMIN_ROLE,
            'guard_name' => 'api',
        ]);
    }

    /**
     * @return void
     */
    public function seedPermissionsForAdminRole(): void
    {
        $adminRole = $this->seedAdminRole();

        foreach (UserPermission::getAllValuesAsArray() as $permissionName) {
            Permission::query()->firstOrCreate([
                'name'       => $permissionName,
                'guard_name' => 'api',
            ]);
        }

        $adminRole->syncPermissions(Permission::all());
    }
}
