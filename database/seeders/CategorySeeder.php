<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Fashion',
            'Elektronik',
            'Makanan',
            'Minuman',
            'Kesehatan',
            'Olahraga'
        ];

        foreach ($categories as $category) {

            Category::create([
                'category_name' => $category
            ]);

        }
    }
}