<?php

namespace App\Console\Commands;

use App\Jobs\SendLicenseAlert;
use App\Models\User;
use Illuminate\Console\Command;

class LicenseAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:license-alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send notification alert to drivers before license date expires';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::whereHas('vehicleDoc',function($q){
            return $q->whereRaw("DATEDIFF(license_expire_date,Now())<3");
        })->chunk(10,function($drivers){
             dispatch(new SendLicenseAlert($drivers));
        });
    }
}
