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
}
