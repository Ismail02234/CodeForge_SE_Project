<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UniversityController extends Controller
{
    private function query()
    {
        return DB::table('universities as u')
            ->leftJoin('users as m', 'm.university', '=', 'u.name')
            ->leftJoin('submissions as s', function ($j) {
                $j->on('s.user_id', '=', 'm.id')->where('s.verdict', 'AC');
            })
            ->groupBy('u.name', 'u.city')
            ->selectRaw('u.name,u.city,COUNT(DISTINCT m.id) members,COALESCE(ROUND(AVG(m.rating)),0) avg_rating,
                COUNT(DISTINCT s.problem_id) solved_problems,COUNT(DISTINCT s.id) accepted_submissions');
    }

    public function index()
    {
        return $this->query()->orderByDesc('avg_rating')->orderByDesc('solved_problems')->get();
    }

    public function compare(Request $request)
    {
        $a = (string) $request->query('a', '');
        $b = (string) $request->query('b', '');
        $all = $this->index();
        $find = fn ($name) => collect($all)->first(fn ($r) => $r->name === $name);

        return ['universities' => $all, 'left' => $a ? $find($a) : null, 'right' => $b ? $find($b) : null];
    }
}
