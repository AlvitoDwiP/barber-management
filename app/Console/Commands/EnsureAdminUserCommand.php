<?php

namespace App\Console\Commands;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Console\Command;

class EnsureAdminUserCommand extends Command
{
    protected $signature = 'app:ensure-admin-user';

    protected $description = 'Ensure the default admin login exists in the current database';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true]);

        $this->components->info('Admin user is ready.');
        $this->line('Email: '.AdminUserSeeder::DEFAULT_EMAIL);
        $this->line('Password: '.AdminUserSeeder::DEFAULT_PASSWORD);

        return self::SUCCESS;
    }
}
