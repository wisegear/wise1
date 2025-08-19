<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {    
        DB::table('users')->updateOrInsert(
            ['email' => 'lee@wisener.net'],
            [
                'name' => 'Lee Wisener',
                'name_slug' => 'lee-wisener',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'banned@wisener.net'],
            [
                'name' => 'Banned Member',
                'name_slug' => 'banned-member',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'pending@wisener.net'],
            [
                'name' => 'Pending Member',
                'name_slug' => 'pending-member',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'member@wisener.net'],
            [
                'name' => 'Member',
                'name_slug' => 'member',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
