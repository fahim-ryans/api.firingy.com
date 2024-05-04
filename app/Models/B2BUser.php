<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;


class B2BUser extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = "b2b_users";
    protected $primaryKey = "b2b_user_id";

    protected $fillable = [
                            'name',
                            'org_name',
                            'email',
                            'phone',
                            'address',
                            'password',
                            'avatar',
                            'is_active',
                            'verify_password_token',
                            'verify_password_token_time'
                        ];

    protected $hidden = [
                            'password',
                            'remember_token',
                        ];

    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    //     'password' => 'hashed',
    // ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

}
