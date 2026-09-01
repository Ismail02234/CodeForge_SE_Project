<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SqlChallenge extends Model
{
    protected $table = 'sql_challenges';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['order_sensitive' => 'boolean', 'max_score' => 'integer'];
}
