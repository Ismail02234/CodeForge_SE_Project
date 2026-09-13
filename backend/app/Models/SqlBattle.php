<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SqlBattle extends Model
{
    protected $table = 'sql_battles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime', 'completed_at' => 'datetime'];

    public function challenge()
    {
        return $this->belongsTo(SqlChallenge::class, 'challenge_id');
    }

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function attempts()
    {
        return $this->hasMany(SqlAttempt::class, 'battle_id');
    }
}
