<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = include database_path('seeders\initial_data\settings.php');

        foreach($settings as $setting)
        {
            Setting::updateOrCreate($setting);
        }
        Cache::forget('settings');

    }
}
