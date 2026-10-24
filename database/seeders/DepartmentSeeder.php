<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Department::updateOrCreate(
            ['slug' => 'laquage'],
            ['name' => 'Laquage']
        );

        \App\Models\Department::updateOrCreate(
            ['slug' => 'emballage'],
            ['name' => 'Emballage']
        );
    }
}