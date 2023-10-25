<?php

namespace Database\Seeders;

use App\Models\CarBrand;
use App\Models\CarType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('car_brands')->delete();
        $car_brands_ar = ['مرسيدس','تويوتا','ميني كوبر','اتوبيس'];
        $car_brands_en = ['marcedes','toyota','mini-cober','Bus'];
        foreach($car_brands_en as $key => $type)
        {
            CarBrand::create([
                'en' => ['name'=>$car_brands_en[$key]],
                'ar' => ['name'=>$car_brands_ar[$key]]
            ]);
        }
    }
}
