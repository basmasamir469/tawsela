<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NewDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:new-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'lets start a new day with a new hope !';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         User::whereHas('roles',function($q){
            return $q->where('name','driver');
         })->update([
         'active_hours'=> Carbon::createFromTime(0, 0)
        ]);
    }
}
