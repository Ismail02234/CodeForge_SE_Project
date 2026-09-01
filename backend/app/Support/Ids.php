<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Ids
{
    public static function make(string $prefix): string
    {
        return $prefix.'_'.strtolower(Str::random(20));
    }
}
