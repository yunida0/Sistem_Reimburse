<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Operasional', 'code' => 'OPS'],
            ['name' => 'Keuangan',    'code' => 'FIN'],
            ['name' => 'Marketing',   'code' => 'MKT'],
            ['name' => 'IT',          'code' => 'IT'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }
    }
}
