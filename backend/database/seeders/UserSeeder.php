<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Reset all normal users, but keep admin
        DB::table('users')->where('email', '!=', 'admin@example.com')->delete();

        // Create normal users
        User::factory()->create([
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'password' => bcrypt('password123'),
        ]);

        User::factory()->create([
            'name' => 'Charlie Davis',
            'email' => 'charlie@example.com',
            'password' => bcrypt('password123'),
        ]);

        // More normal users via factory
        User::factory(7)->create();
    }
}
