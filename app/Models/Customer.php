<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $guard = 'customer';
    protected $primaryKey = 'customer_id';

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $fillable = [
        'customer_id_inv', 'group_id', 'email', 'email_verify_token', 'is_email_verified', 'code',
        'phone', 'verify_token', 'phone_verify_token_time', 'phone_verify_token_count',
        'status', 'verify_password_token', 'verify_password_token_count',
        'customer_is_exist', 'password', 'created_by', 'updated_by'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
