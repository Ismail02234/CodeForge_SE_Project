<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return ['query' => $q, 'users' => [], 'problems' => [], 'universities' => []];
        }
        $like = '%'.mb_substr($q, 0, 80).'%';

        return [
            'query' => $q,
            'users' => DB::table('users')->where(function ($x) use ($like) {
                $x->where('username', 'like', $like)->orWhere('university', 'like', $like);
            })->orderByDesc('rating')->limit(12)->get(['id', 'username', 'rating', 'rank', 'university']),
            'problems' => DB::table('problems')->where(function ($x) use ($like) {
                $x->where('title', 'like', $like)->orWhere('topic', 'like', $like)->orWhere('tags', 'like', $like);
            })->limit(12)->get(['id', 'title', 'topic', 'difficulty']),
            'universities' => DB::table('universities')->where('name', 'like', $like)->orWhere('city', 'like', $like)->limit(12)->get(),
        ];
    }
}
