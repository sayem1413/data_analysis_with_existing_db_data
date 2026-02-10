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
    protected $signature = 'split:user-country-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Split the APP users country codes from mobile number';

    protected $last_appuser_id = 0;

    protected $chunk_limit = 1000;

    protected $total_appuser = 0;

    protected $total_appuser_with_number = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {   
        do {
            $appUsers = AppUser::select([
                'userId',
                'userName',
                'countryCode'
            ])
                ->where('userId', '>', $this->last_appuser_id)
                ->orderBy('userId')
                ->limit($this->chunk_limit)
                ->get();

            if ($appUsers->isEmpty()) {
                break;
            }

            foreach ($appUsers as $appUser) {
                $this->total_appuser++;
                if($this->isValidMobile($appUser->userName)) {
                    try {
                        $countryCode = getCountryCodeByPhone($appUser->userName);

                        if ($countryCode && $appUser->countryCode !== $countryCode) {
                            $appUser->update([
                                'countryCode' => $countryCode,
                            ]);
                            $this->total_appuser_with_number++;
                        }
                    } catch (\Exception $e) {
                        $this->error("User {$appUser->userId}: {$e->getMessage()}");
                    }
                }
            }

            $this->last_appuser_id = $appUsers->last()->userId;
        } while ($appUsers->isNotEmpty());

        $this->info('Total number of app users = ' . $this->total_appuser);
        $this->info('Total number of app users with mobile number as UserName = ' . $this->total_appuser_with_number);
    }

    private function isValidMobile(?string $value): bool
    {
        return $value !== null && (preg_match('/^[0-9]{8,15}$/', $value) || preg_match('/^[1-9][0-9]{7,14}$/', $value));
    }
}
