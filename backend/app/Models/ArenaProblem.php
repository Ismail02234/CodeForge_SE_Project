<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArenaProblem extends Model
{
    protected $table = 'arena_problems';

    protected $primaryKey = 'problem_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['problem_id', 'title', 'topic', 'difficulty'];

    public function submissions()
    {
        return $this->hasMany(ArenaSubmission::class, 'problem_id', 'problem_id');
    }
}
