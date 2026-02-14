<?php

namespace App\Models;

use App\Models\Traits\BaseModel;

class Country extends BaseModel
{
    protected $table = 'countries';

    protected $fillable = [
        'title',
        'title_bn',
        'code',
        'phone_code',
        'nationality_en',
        'nationality_bn',
        'logo',
        'display_sequence',
        'active_status',
        'default_currency_id',
        'created_by',
        'updated_by',
        'bmet_country_ref_code',
        'one_stop_service',
        'sms_gateway',
    ];

    public function embassies()
    {
        return $this->hasMany(Embassy::class, 'country_id');
    }
}
