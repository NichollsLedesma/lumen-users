<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class CustomConfig extends Model
{
    public const FACTOR_AUTH_TYPE_EMAIL = "email";
    public const FACTOR_AUTH_TYPE_PHONE = "phone";

    protected $fillable = [
        'userId','factor_authentication', 'code_auth'
    ];
}
