<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppUser;

class SplitAppUserCountryCodeFromMobileNo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'split:app-user-country-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Split the APP users country codes from mobile number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userIdsWithPlus88 = AppUser::where('userName', 'like', '%+%')->pluck('userName','userId')->toArray();
        dd(
            $userIdsWithPlus88,
            array_keys($userIdsWithPlus88)
        );
        $users = AppUser::whereRaw("userName REGEXP '^[0-9]+$'")
            ->whereNull('countryCode')
            ->orderBy('userId')
            ->chunkById(1000, function ($users) {
                foreach ($users as $user) {
                    dump(
                        $user->userName
                    );
                }
            }, 'userId');
        
        dd(
            $users
        );
    }
}
