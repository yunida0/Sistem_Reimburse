<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Transportasi', 'code' => 'TRN',  'requires_receipt' => true],
            ['name' => 'Akomodasi',    'code' => 'HTL',  'requires_receipt' => true],
            ['name' => 'Konsumsi',     'code' => 'KON',  'requires_receipt' => true],
            ['name' => 'Lainnya',      'code' => 'LAIN', 'requires_receipt' => true],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
