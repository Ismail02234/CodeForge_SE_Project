<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'submissions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['submitted_at' => 'datetime', 'elapsed_seconds' => 'integer', 'runtime_ms' => 'integer', 'memory_kb' => 'integer'];
}
