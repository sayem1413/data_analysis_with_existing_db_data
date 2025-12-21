<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesiredSkill extends Model
{
    protected $table = 'desired_skills';

    protected $fillable = [
        'title',
        'title_bn',
        'remarks',
        'parent_id',
        'display_sequence',
        'active_status',
        'created_by',
        'updated_by',
        'icon_path',
        'is_selected',
        'bmet_reference_code',
    ];
}
