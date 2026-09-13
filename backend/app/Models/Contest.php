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

    public function contestProblems()
    {
        return $this->hasMany(ContestProblem::class);
    }

    public function problems()
    {
        return $this->belongsToMany(Problem::class, 'contest_problems', 'contest_id', 'problem_id');
    }

    public function participants()
    {
        return $this->hasMany(ContestParticipant::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
