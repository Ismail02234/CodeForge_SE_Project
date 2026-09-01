<?php

namespace App\Http\Controllers;

use App\Support\Ids;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class ContestController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('contests as c')
            ->leftJoin('contest_participants as cp', 'cp.contest_id', '=', 'c.id')
            ->groupBy('c.id', 'c.name', 'c.type', 'c.starts_at', 'c.status', 'c.created_by', 'c.created_at')
            ->selectRaw('c.*, COUNT(cp.user_id) participants');

        if ($request->filled('status')) {
            $q->where('c.status', $request->query('status'));
        }
        if ($request->filled('type')) {
            $q->where('c.type', $request->query('type'));
        }

        return [
            'contests' => $q->orderByRaw("FIELD(c.status,'Active','Upcoming','Past')")->orderBy('c.starts_at')->get(),
            'users' => DB::table('users')->where('id', '<>', $request->user()->id)->orderByDesc('rating')->limit(100)->get(['id', 'username', 'rating', 'rank']),
            'problems' => DB::table('problems')->orderBy('title')->get(['id', 'title', 'difficulty']),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'problem_ids' => ['required', 'array', 'min:1'],
            'problem_ids.*' => ['string', Rule::exists('problems', 'id')],
        ]);

        $id = Ids::make('c');
        DB::transaction(function () use ($request, $data, $id) {
            DB::table('contests')->insert([
                'id' => $id, 'name' => $data['name'], 'type' => 'Local', 'starts_at' => $data['starts_at'],
                'status' => strtotime($data['starts_at']) <= time() ? 'Active' : 'Upcoming',
                'created_by' => $request->user()->id, 'created_at' => now(),
            ]);
            foreach (array_unique($data['problem_ids']) as $pid) {
                DB::table('contest_problems')->insert(['contest_id' => $id, 'problem_id' => $pid, 'points' => 100]);
            }
            DB::table('contest_participants')->insert(['contest_id' => $id, 'user_id' => $request->user()->id, 'score' => 0, 'joined_at' => now()]);
        });

        return response()->json(['id' => $id], 201);
    }

    public function show(Request $request, string $id)
    {
        $contest = DB::table('contests')->where('id', $id)->first();
        abort_if(! $contest, 404, 'Contest not found.');

        if ($contest->status === 'Upcoming' && strtotime($contest->starts_at) <= time()) {
            DB::table('contests')->where('id', $id)->where('status', 'Upcoming')->update(['status' => 'Active']);
            $contest = DB::table('contests')->where('id', $id)->first();
        }

        $problems = DB::table('contest_problems as cp')->join('problems as p', 'p.id', '=', 'cp.problem_id')
            ->where('cp.contest_id', $id)->get(['p.id', 'p.title', 'p.topic', 'p.difficulty', 'cp.points']);

        $leaderboard = DB::table('contest_participants as cp')->join('users as u', 'u.id', '=', 'cp.user_id')
            ->where('cp.contest_id', $id)->orderByDesc('cp.score')->orderBy('cp.joined_at')
            ->get(['u.id', 'u.username', 'u.rating', 'u.rank', 'cp.score', 'cp.joined_at']);

        return [
            'contest' => $contest, 'problems' => $problems, 'leaderboard' => $leaderboard,
            'joined' => DB::table('contest_participants')->where('contest_id', $id)->where('user_id', $request->user()->id)->exists(),
        ];
    }

    public function join(Request $request, string $id)
    {
        abort_unless(DB::table('contests')->where('id', $id)->exists(), 404);
        DB::table('contest_participants')->insertOrIgnore(['contest_id' => $id, 'user_id' => $request->user()->id, 'score' => 0, 'joined_at' => now()]);

        return ['message' => 'Joined contest.'];
    }

    public function close(string $id)
    {
        DB::table('contests')->where('id', $id)->update(['status' => 'Past']);

        return ['message' => 'Contest closed.'];
    }

    public function duel(Request $request)
    {
        $data = $request->validate([
            'opponent_id' => ['required', 'string', Rule::exists('users', 'id')],
            'problem_id' => ['required', 'string', Rule::exists('problems', 'id')],
        ]);
        if ($data['opponent_id'] === $request->user()->id) {
            throw new RuntimeException('Choose another user.');
        }

        $id = Ids::make('duel');
        DB::transaction(function () use ($request, $data, $id) {
            $opponent = DB::table('users')->where('id', $data['opponent_id'])->value('username');
            DB::table('contests')->insert([
                'id' => $id, 'name' => 'Duel: '.$request->user()->username.' vs '.$opponent, 'type' => 'Duel',
                'starts_at' => now(), 'status' => 'Active', 'created_by' => $request->user()->id, 'created_at' => now(),
            ]);
            DB::table('contest_problems')->insert(['contest_id' => $id, 'problem_id' => $data['problem_id'], 'points' => 500]);
            DB::table('contest_participants')->insert([
                ['contest_id' => $id, 'user_id' => $request->user()->id, 'score' => 0, 'joined_at' => now()],
                ['contest_id' => $id, 'user_id' => $data['opponent_id'], 'score' => 0, 'joined_at' => now()],
            ]);
        });

        return response()->json(['id' => $id], 201);
    }
}
