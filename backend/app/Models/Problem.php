<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    protected $table = 'problems';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'title', 'topic', 'difficulty', 'description', 'tags', 'starter_code', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function problemSessions()
    {
        return $this->hasMany(ProblemSession::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function contestProblems()
    {
        return $this->hasMany(ContestProblem::class);
    }

    public function contests()
    {
        return $this->belongsToMany(Contest::class, 'contest_problems', 'problem_id', 'contest_id');
    }

    public function ghostRaces()
    {
        return $this->hasMany(GhostRace::class);
    }

    public function learningProblems()
    {
        return $this->hasMany(LearningProblem::class);
    }
}
