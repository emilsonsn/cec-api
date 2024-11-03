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
            'phone' => '83999999999',
            'whatsapp' => '83999999999',
            'cpf_cnpj' => '0000000000',
            'birth_date' => '2001-12-18',
            'is_admin' => true,
            'is_active' => true,
        ]);
    }
}