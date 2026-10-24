<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::updateOrCreate(
            ['slug' => 'laquage'],
            ['name' => 'Laquage']
        );

        Department::updateOrCreate(
            ['slug' => 'emballage'],
            ['name' => 'Emballage']
        );
    }
}
