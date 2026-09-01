<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function options(Request $request)
    {
        return DB::table('users')
            ->where('id', '<>', $request->user()->id)
            ->orderByDesc('rating')
            ->orderBy('username')
            ->limit(150)
            ->get(['id', 'username', 'rating', 'rank', 'university']);
    }
}
