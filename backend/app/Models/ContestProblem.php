<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContestProblem extends Model
{
    protected $table = 'contest_problems';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['contest_id', 'problem_id', 'points'];

    protected $casts = ['points' => 'integer'];

    public $timestamps = false;

    public function contest()
    {
        return $this->belongsTo(Contest::class, 'contest_id', 'id');
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class, 'problem_id', 'id');
    }
}
