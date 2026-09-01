<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SqlBattle extends Model
{
    protected $table = 'sql_battles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime', 'completed_at' => 'datetime'];
}
