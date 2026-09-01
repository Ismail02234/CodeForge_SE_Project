<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProblemSession extends Model
{
    protected $table = 'problem_sessions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime', 'solve_time_seconds' => 'integer'];
}
