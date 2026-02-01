<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run()
    {
        $user = User::first() ?? User::factory()->create();
        Brand::factory()->count(10)->for($user)->create();
    }
}
