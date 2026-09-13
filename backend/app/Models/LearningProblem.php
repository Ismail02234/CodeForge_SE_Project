<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningProblem extends Model
{
    protected $table = 'learning_problems';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'learning_module_id',
        'problem_id',
        'stage',
        'sort_order',
        'created_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    public function module()
    {
        return $this->belongsTo(LearningModule::class, 'learning_module_id', 'id');
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class, 'problem_id', 'id');
    }
}
