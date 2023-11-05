<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AmountsDuo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:amounts-duo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'when amounts duo of a driver exceeds 2000 driver account must be closed until a driver pay his amounts duo to the app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::whereHas('roles',function($q){
            return $q->where('name','driver');
         })->where('debit','>',2000)->update([
             'account_status' => 0
        ]);
    }
}
