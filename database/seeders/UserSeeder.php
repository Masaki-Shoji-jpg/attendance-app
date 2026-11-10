<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create(
            [
                'name' => 'ユーザー1',
                'email' => 'user1@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );
        User::create(
            [
                'name' => 'ユーザー2',
                'email' => 'user2@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );
        User::create(
            [
                'name' => 'ユーザー3',
                'email' => 'user3@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );
    }
}
