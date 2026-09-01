<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'username', 'password', 'role', 'rating', 'university', 'rank', 'created_at'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['rating' => 'integer', 'created_at' => 'datetime'];
}
