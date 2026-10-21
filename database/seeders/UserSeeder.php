<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $ops = Department::where('code', 'OPS')->first();
        $fin = Department::where('code', 'FIN')->first();
        $mkt = Department::where('code', 'MKT')->first();

        // Admin
        $admin = User::firstOrCreate(['email' => 'admin@reimbust.test'], [
            'name'          => 'Admin Reimbust',
            'password'      => Hash::make('password'),
            'role'          => UserRole::Admin,
            'department_id' => null,
            'manager_id'    => null,
            'is_active'     => true,
        ]);

        // Atasan (Budi)
        $budi = User::firstOrCreate(['email' => 'budi@reimbust.test'], [
            'name'          => 'Budi Santoso',
            'password'      => Hash::make('password'),
            'role'          => UserRole::Approver,
            'department_id' => $ops->id,
            'manager_id'    => null,
            'is_active'     => true,
        ]);

        // Pegawai (Rudi)
        $rudi = User::firstOrCreate(['email' => 'rudi@reimbust.test'], [
            'name'          => 'Rudi Hartono',
            'password'      => Hash::make('password'),
            'role'          => UserRole::Employee,
            'department_id' => $ops->id,
            'manager_id'    => $budi->id,
            'is_active'     => true,
        ]);

        // Pegawai (Siti)
        $siti = User::firstOrCreate(['email' => 'siti@reimbust.test'], [
            'name'          => 'Siti Aminah',
            'password'      => Hash::make('password'),
            'role'          => UserRole::Employee,
            'department_id' => $mkt->id,
            'manager_id'    => $budi->id,
            'is_active'     => true,
        ]);

        // Finance (Sari)
        $sari = User::firstOrCreate(['email' => 'sari@reimbust.test'], [
            'name'          => 'Sari Wulandari',
            'password'      => Hash::make('password'),
            'role'          => UserRole::Finance,
            'department_id' => $fin->id,
            'manager_id'    => null,
            'is_active'     => true,
        ]);

        // Kasir (Andi)
        $kasir = User::firstOrCreate(['email' => 'kasir@reimbust.test'], [
            'name'          => 'Andi Pratama',
            'password'      => Hash::make('password'),
            'role'          => UserRole::Cashier,
            'department_id' => $fin->id,
            'manager_id'    => null,
            'is_active'     => true,
        ]);
    }
}
