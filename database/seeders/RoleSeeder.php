<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Ruoli base della piattaforma (E4.1.3):
 *  - super-admin: gestore Replisa (tu), nessun tenant, vede tutto
 *  - owner: titolare dell'attività cliente
 *  - operator: dipendente con accesso ridotto
 *
 * Idempotente: usa firstOrCreate.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_OWNER, User::ROLE_OPERATOR] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
