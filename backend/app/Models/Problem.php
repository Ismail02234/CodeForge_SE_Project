<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    protected $table = 'problems';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'title', 'topic', 'difficulty', 'description', 'tags', 'starter_code', 'created_at'];
}
