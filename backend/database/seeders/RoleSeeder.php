<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access'
            ],
            [
                'name' => 'Editor',
                'slug' => 'editor',
                'description' => 'Can edit content'
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Can view content'
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
} 