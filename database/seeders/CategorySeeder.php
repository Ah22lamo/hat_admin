<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_code' => 'CAT_01',
                'name' => 'ألعاب أطفال',
                'slug' => 'atfal-toys',
            ],
            [
                'category_code' => 'CAT_02',
                'name' => 'ألعاب تعليمية',
                'slug' => 'educational-toys',
            ],
            [
                'category_code' => 'CAT_03',
                'name' => 'ألعاب منوعة',
                'slug' => 'various-toys',
            ],
            [
                'category_code' => 'CAT_04',
                'name' => 'أدوات سباحة',
                'slug' => 'swimming-tools',
            ],
            [
                'category_code' => 'CAT_05',
                'name' => 'أدوات المطر',
                'slug' => 'rain-tools',
            ],
            [
                'category_code' => 'CAT_06',
                'name' => 'أدوات مدرسية',
                'slug' => 'school-supplies',
            ],
            [
                'category_code' => 'CAT_07',
                'name' => 'حقائب مدرسية',
                'slug' => 'school-bags',
            ],
            [
                'category_code' => 'CAT_08',
                'name' => 'حقائب منوعة',
                'slug' => 'various-bags',
            ],
            [
                'category_code' => 'CAT_09',
                'name' => 'سبحة تسبيح',
                'slug' => 'tasbeeh',
            ],
            [
                'category_code' => 'CAT_10',
                'name' => 'أصناف أخرى (خردوات)',
                'slug' => 'other-items',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'category_code' => $category['category_code'],
                ],
                [
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'is_active' => true,
                ]
            );
        }
    }
}