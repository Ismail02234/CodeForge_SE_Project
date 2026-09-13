<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'username', 'password', 'role', 'rating', 'university', 'rank', 'created_at'];

    protected $hidden = ['password'];

    protected $casts = ['rating' => 'integer', 'created_at' => 'datetime'];

    public function university()
    {
        return $this->belongsTo(University::class, 'university', 'name');
    }

    public function problemSessions()
    {
        return $this->hasMany(ProblemSession::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function contestParticipants()
    {
        return $this->hasMany(ContestParticipant::class);
    }

    public function ghostRacesAsChallenger()
    {
        return $this->hasMany(GhostRace::class, 'challenger_id');
    }

    public function ghostRacesAsGhost()
    {
        return $this->hasMany(GhostRace::class, 'ghost_user_id');
    }

    public function sqlBattlesAsPlayer1()
    {
        return $this->hasMany(SqlBattle::class, 'player1_id');
    }

    public function sqlBattlesAsPlayer2()
    {
        return $this->hasMany(SqlBattle::class, 'player2_id');
    }

    public function sqlBattlesAsWinner()
    {
        return $this->hasMany(SqlBattle::class, 'winner_id');
    }

    public function sqlAttempts()
    {
        return $this->hasMany(SqlAttempt::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
