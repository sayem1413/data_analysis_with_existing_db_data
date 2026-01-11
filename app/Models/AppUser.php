<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUser extends Model
{
    protected $table = 'userinfo';
    
    protected $primaryKey = 'userId';

    protected $rememberTokenName = 'passPin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'userName',
        'uniqueId',
        'password',
        'fullName',
        'mobileNo',
        'countryCode',
        'email',
        'smsPin',
        'smsPinStatus',
        'profileImage',
        'userType',
        'passPin',
        'passPinValidation',
        'publicKey',
        'privateKey',
        'organizationId',
        'departmentId',
        'isUnitHead',
        'imei',
        'isOrgApproved',
        'isGpsEnable',
        'org_office_location_id',
        'office_employee_id',
        'employee_reference_id',
        'email_verified',
        'mobile_verified',
        'status',
        'created_by',
        'updated_by',
        'approved_by_admin',
        'destination_country_id',
        'reg_stat',
        'home_my_app_stat',
        'current_lat',
        'current_lon',
        'deleted_at',
        'deleted_by',
        'last_used_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'passPin',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];
}
