<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArenaSubmission extends Model
{
    protected $table = 'arena_submissions';

    protected $primaryKey = 'submission_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['submission_id', 'user_id', 'problem_id', 'verdict', 'runtime_ms'];

    protected $casts = ['runtime_ms' => 'integer'];

    public function user()
    {
        return $this->belongsTo(ArenaUser::class, 'user_id', 'user_id');
    }

    public function problem()
    {
        return $this->belongsTo(ArenaProblem::class, 'problem_id', 'problem_id');
    }
}
