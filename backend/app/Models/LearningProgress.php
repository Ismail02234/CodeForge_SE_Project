<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningProgress extends Model
{
    protected $table = 'learning_progress';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'learning_module_id',
        'learn_completed',
        'play_completed',
        'prove_completed',
        'learn_score',
        'play_score',
        'prove_score',
        'mastery_score',
        'attempts',
        'hints_used',
        'started_at',
        'completed_at',
        'created_at',
        'learn_accuracy',
        'play_accuracy',
        'prove_accuracy',
        'concept_mastery',
        'weakness_signal',
        'repeated_failed_concepts',
        'learn_attempts',
        'play_attempts',
        'prove_attempts',
        'learn_correct',
        'play_correct',
        'prove_correct',
        'learn_completed_steps',
    ];

    protected $casts = [
        'learn_completed' => 'boolean',
        'play_completed' => 'boolean',
        'prove_completed' => 'boolean',
        'learn_score' => 'integer',
        'play_score' => 'integer',
        'prove_score' => 'integer',
        'mastery_score' => 'integer',
        'attempts' => 'integer',
        'hints_used' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'learn_accuracy' => 'integer',
        'play_accuracy' => 'integer',
        'prove_accuracy' => 'integer',
        'concept_mastery' => 'integer',
        'repeated_failed_concepts' => 'array',
        'learn_attempts' => 'integer',
        'play_attempts' => 'integer',
        'prove_attempts' => 'integer',
        'learn_correct' => 'integer',
        'play_correct' => 'integer',
        'prove_correct' => 'integer',
        'learn_completed_steps' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function module()
    {
        return $this->belongsTo(LearningModule::class, 'learning_module_id', 'id');
    }
}
