<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallApp extends Command
{
    protected $signature = 'app:install';

    protected $description = 'Crée les départements et le premier compte administrateur (sans effet s\'ils existent déjà).';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => DepartmentSeeder::class, '--force' => true]);
        $this->info('Départements : OK');

        if (User::where('role', 'admin')->exists()) {
            $this->info('Un administrateur existe déjà : rien à créer.');

            return self::SUCCESS;
        }

        $email = config('install.admin_email') ?: 'admin@example.com';
        $password = config('install.admin_password');
        $generated = false;

        if (! $password) {
            $password = Str::password(16, symbols: false);
            $generated = true;
        }

        User::create([
            'name' => config('install.admin_name') ?: 'Administrateur',
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'department_id' => null,
        ]);

        $this->info("Administrateur créé : {$email}");

        if ($generated) {
            $this->warn("Mot de passe généré (à changer après connexion) : {$password}");
        }

        return self::SUCCESS;
    }
}
