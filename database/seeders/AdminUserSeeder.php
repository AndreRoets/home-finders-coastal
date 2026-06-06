<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the initial admin account. Additional users are created from within
     * the marketing panel's Users section.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'andre@corexos.co.za'],
            [
                'name' => 'Andre',
                'password' => Hash::make('Mineme098@'),
                'email_verified_at' => now(),
            ],
        );
    }
}
