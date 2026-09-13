<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContestParticipant extends Model
{
    protected $table = 'contest_participants';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['contest_id', 'user_id', 'score', 'joined_at'];

    protected $casts = ['score' => 'integer', 'joined_at' => 'datetime'];

    public $timestamps = false;

    public function contest()
    {
        return $this->belongsTo(Contest::class, 'contest_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
