<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->delete();
        $roles = ['user','driver','admin'];

        foreach($roles as $role)
        {
            Role::create([
                'name' =>$role,
                'guard_name'=>'api'
            ]);
        }
    }
}
