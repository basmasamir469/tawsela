<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->Call(CarTypeSeeder::class);
        $this->Call(CarColorSeeder::class);
        $this->Call(CarBrandSeeder::class);
        $this->Call(SettingSeeder::class);
        $admin = User::create([
             'name'              => 'admin',
             'email'             => 'admin@admin.com',
             'password'          => Hash::make(123456),
             'phone'             => '01000122737',
             'address'           => 'egypt',
             'is_active_email'   =>1
         ]);
        $role = Role::where('name','admin')->first();
        $admin->assignRole($role);
    }
}
