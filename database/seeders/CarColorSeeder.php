<?php

namespace Database\Seeders;

use App\Models\CarColor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('car_colors')->delete();
        $car_colors_ar = ['احمر','اصفر','اخضر','رمادي'];
        $car_colors_en = ['red','yellow','green','grey'];
        foreach($car_colors_en as $key => $type)
        {
            CarColor::create([
                'en' => ['name'=>$car_colors_en[$key]],
                'ar' => ['name'=>$car_colors_ar[$key]]
            ]);
        }
    }
}
