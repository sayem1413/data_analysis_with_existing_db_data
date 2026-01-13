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

    protected $chunk_limit = 1000;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /* $userIdsWithPlus88 = AppUser::where('userName', 'like', '%+%')->pluck('userName', 'userId')->toArray();
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
            }, 'userId'); */

        do {
            $appUsers = AppUser::select([
                'userId',
                'userName',
                'uniqueId',
                'mobileNo',
                'countryCode',
                'email',
            ])
                ->orderBy('id')
                ->limit($this->chunk_limit)
                ->get();

            if ($appUsers->isEmpty()) {
                break;
            }

            /* foreach ($appUsers as $appUser) {
                if(true) {

                }
            } */
        } while ($appUsers->isNotEmpty());
    }
}
