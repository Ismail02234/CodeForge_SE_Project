<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GhostRace extends Model
{
    protected $table = 'ghost_races';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime', 'ghost_time' => 'integer', 'challenger_time' => 'integer', 'playback_speed' => 'integer'];

    public function challenger()
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function ghostUser()
    {
        return $this->belongsTo(User::class, 'ghost_user_id');
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }

    public function ghostSession()
    {
        return $this->belongsTo(ProblemSession::class, 'ghost_session_id');
    }

    public function challengerSession()
    {
        return $this->belongsTo(ProblemSession::class, 'challenger_session_id');
    }
}
