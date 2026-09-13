<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SqlAttempt extends Model
{
    protected $table = 'sql_attempts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['execution_time_ms' => 'decimal:3', 'efficiency_score' => 'integer', 'score' => 'integer', 'submitted_at' => 'datetime'];

    public function battle()
    {
        return $this->belongsTo(SqlBattle::class, 'battle_id');
    }

    public function challenge()
    {
        return $this->belongsTo(SqlChallenge::class, 'challenge_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
