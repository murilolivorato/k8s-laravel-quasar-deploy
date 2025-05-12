<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign admin role
        $adminRole = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($adminRole);

        // Create editor user
        $editor = User::create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign editor role
        $editorRole = Role::where('slug', 'editor')->first();
        $editor->roles()->attach($editorRole);

        // Create regular user
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign viewer role
        $viewerRole = Role::where('slug', 'viewer')->first();
        $user->roles()->attach($viewerRole);
    }
}
