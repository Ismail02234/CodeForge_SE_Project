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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function ghostRacesAsGhost()
    {
        return $this->hasMany(GhostRace::class, 'ghost_session_id');
    }

    public function ghostRacesAsChallenger()
    {
        return $this->hasMany(GhostRace::class, 'challenger_session_id');
    }
}
