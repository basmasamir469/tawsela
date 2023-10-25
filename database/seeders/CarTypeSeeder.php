<?php

namespace Database\Seeders;

use App\Models\CarType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CarTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('car_types')->delete();
        $car_types_ar = [['تاكسي',50],['ملاكي',100],['ميكروباص',300],['اتوبيس',1000]];
        $car_types_en = ['Taxi','Car','Micorbus','Bus'];
        foreach($car_types_en as $key => $type)
        {
           $car_type =  CarType::create([
                'en'     => ['name'=>$car_types_en[$key]],
                'ar'     => ['name'=>$car_types_ar[$key][0]],
                'price'  => $car_types_ar[$key][1]
            ]);
           $car_images = ['cars.jpg','cars.jpg','cars.jpg','cars.jpg','cars.jpg'];
           $path='public/images/cars/'.$car_images[$key];
           $car_type->addMedia($path)->toMediaCollection('car-images');
           $new_path = $car_type->getFirstMedia('car-images')->getPath();
           File::copy($new_path,$path);
        }
    }
}
