<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningStep extends Model
{
    protected $table = 'learning_steps';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'learning_module_id',
        'step_order',
        'type',
        'title',
        'content',
        'question',
        'options',
        'correct_answer',
        'xp_reward',
        'created_at',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'options' => 'array',
        'xp_reward' => 'integer',
        'created_at' => 'datetime',
    ];

    public function module()
    {
        return $this->belongsTo(LearningModule::class, 'learning_module_id', 'id');
    }
}
