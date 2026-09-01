<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    public function users()
    {
        $users = DB::table('users as u')
            ->leftJoinSub(
                DB::table('submissions')
                    ->selectRaw("user_id, COUNT(*) AS submissions, COUNT(DISTINCT CASE WHEN verdict = 'AC' THEN problem_id END) AS solved")
                    ->groupBy('user_id'),
                'stats',
                'stats.user_id',
                '=',
                'u.id'
            )
            ->orderByDesc('u.rating')
            ->orderBy('u.username')
            ->get([
                'u.id', 'u.username', 'u.role', 'u.rating', 'u.university', 'u.rank', 'u.created_at',
                DB::raw('COALESCE(stats.submissions, 0) AS submissions'),
                DB::raw('COALESCE(stats.solved, 0) AS solved'),
            ]);

        return [
            'users' => $users,
            'universities' => DB::table('universities')->orderBy('name')->get(['name', 'city']),
        ];
    }
}
