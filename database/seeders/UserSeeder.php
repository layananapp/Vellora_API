<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;

use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin Marketplace',
            'email' => 'admin@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'Seller Marketplace',
            'email' => 'seller@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'seller'
        ]);

        User::create([
            'name' => 'Buyer Marketplace',
            'email' => 'buyer@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'buyer'
        ]);
    }
}