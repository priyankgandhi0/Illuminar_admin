<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $table = 'admins';

    protected $primaryKey = 'admin_id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'is_deleted',
        'created_date',
        'updated_date'
    ];

    protected $hidden = [
        'password',
    ];
}