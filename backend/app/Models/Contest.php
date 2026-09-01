<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contest extends Model
{
    protected $table = 'contests';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['starts_at' => 'datetime', 'created_at' => 'datetime'];
}
