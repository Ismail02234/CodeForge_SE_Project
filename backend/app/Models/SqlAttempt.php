<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SqlAttempt extends Model
{
    protected $table = 'sql_attempts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['execution_time_ms' => 'float', 'efficiency_score' => 'integer', 'score' => 'integer', 'submitted_at' => 'datetime'];
}
