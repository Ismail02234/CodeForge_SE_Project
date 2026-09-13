<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArenaUser extends Model
{
    protected $table = 'arena_users';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['user_id', 'username', 'university', 'rating'];

    protected $casts = ['rating' => 'integer'];

    public function university()
    {
        return $this->belongsTo(ArenaUniversity::class, 'university', 'name');
    }

    public function submissions()
    {
        return $this->hasMany(ArenaSubmission::class, 'user_id', 'user_id');
    }
}
