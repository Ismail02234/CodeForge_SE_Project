<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArenaUniversity extends Model
{
    protected $table = 'arena_universities';

    protected $primaryKey = 'name';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(ArenaUser::class, 'university', 'name');
    }
}
