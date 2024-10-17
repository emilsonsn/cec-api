<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        User::firstOrCreate([
            'email' => 'admin@admin',
        ],
        [
            'name' => 'Admin',
            'email' => 'admin@admin',
            'password' => Hash::make('admin'),
            'phone' => '83991236636',
            'whatsapp' => '83991236636',
            'cpf_cnpj' => '13754674412',
            'birth_date' => '2001-12-18',
            'is_admin' => true,
            'is_active' => true,
        ]);

        User::firstOrCreate([
            'email' => 'user@user',
        ],
        [
            'name' => 'User',
            'email' => 'user@user',
            'password' => Hash::make('user'),
            'phone' => '83991236636',
            'whatsapp' => '83993236636',
            'cpf_cnpj' => '13754676412',
            'birth_date' => '2001-12-18',
            'is_admin' => true,
            'is_active' => false,
        ]);
    }
}