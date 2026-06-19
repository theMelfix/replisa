<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crea (o promuove) un utente super-admin della piattaforma (E4.1.4).
 * Il super-admin non appartiene a nessun tenant e vede tutti i dati.
 *
 * Uso: php artisan replisa:create-admin [email]
 */
class CreateAdmin extends Command
{
    protected $signature = 'replisa:create-admin {email?}';

    protected $description = 'Crea o promuove un utente a super-admin della piattaforma';

    public function handle(): int
    {
        // Assicura che i ruoli esistano.
        $this->callSilent('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);

        $email = $this->argument('email') ?: text('Email del super-admin', required: true);

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->assignRole(User::ROLE_SUPER_ADMIN);
            $this->info("Utente {$email} promosso a super-admin.");

            return self::SUCCESS;
        }

        $name = text('Nome', default: 'Admin', required: true);
        $plain = password('Password', required: true);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($plain),
            'tenant_id' => null,
        ]);
        $user->assignRole(User::ROLE_SUPER_ADMIN);

        $this->info("Super-admin {$email} creato.");

        return self::SUCCESS;
    }
}
