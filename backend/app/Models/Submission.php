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

    protected $casts = ['submitted_at' => 'datetime', 'elapsed_seconds' => 'integer', 'runtime_ms' => 'integer', 'memory_kb' => 'integer', 'failed_test_case' => 'integer'];

    public function session()
    {
        return $this->belongsTo(ProblemSession::class, 'session_id');
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }
}
