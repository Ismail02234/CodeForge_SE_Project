<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningModule extends Model
{
    protected $table = 'learning_modules';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'title',
        'slug',
        'topic',
        'description',
        'difficulty',
        'estimated_minutes',
        'xp_reward',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'estimated_minutes' => 'integer',
        'xp_reward' => 'integer',
        'created_at' => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(LearningStep::class, 'learning_module_id', 'id');
    }

    public function playChallenges()
    {
        return $this->hasMany(PlayChallenge::class, 'learning_module_id', 'id');
    }

    public function learningProblems()
    {
        return $this->hasMany(LearningProblem::class, 'learning_module_id', 'id');
    }

    public function progress()
    {
        return $this->hasMany(LearningProgress::class, 'learning_module_id', 'id');
    }
}
