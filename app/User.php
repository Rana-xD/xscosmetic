<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'failed_login_attempts',
        'locked_until',
        'lockout_level',
        'barcode',
        'default_clock_in',
        'default_clock_out',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    const SUPERADMIN = 'SUPERADMIN';
    const ADMIN = 'ADMIN';
    const MANAGER = 'MANAGER';
    const STAFF = 'STAFF';

    public function isAdmin()
    {
        return $this->role === self::ADMIN;
    }

    public function isManager()
    {
        return $this->role === self::MANAGER;
    }

    public function isSuperAdmin()
    {
        return $this->role === self::SUPERADMIN;
    }

    public function isStaff()
    {
        return $this->role === self::STAFF;
    }
}
