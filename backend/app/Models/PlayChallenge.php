<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayChallenge extends Model
{
    protected $table = 'play_challenges';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'learning_module_id',
        'type',
        'title',
        'instructions',
        'config',
        'xp_reward',
        'time_limit',
        'created_at',
    ];

    protected $casts = [
        'config' => 'array',
        'xp_reward' => 'integer',
        'time_limit' => 'integer',
        'created_at' => 'datetime',
    ];

    public function module()
    {
        return $this->belongsTo(LearningModule::class, 'learning_module_id', 'id');
    }
}
