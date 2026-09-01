<?php

namespace App\Http\Controllers;

use App\Services\SqlBattleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SqlBattleController extends Controller
{
    private function service(): SqlBattleService
    {
        return new SqlBattleService(DB::connection()->getPdo());
    }

    public function challenges()
    {
        return $this->service()->challenges();
    }

    public function leaderboard()
    {
        return $this->service()->leaderboard(20);
    }

    public function recent(Request $request)
    {
        return $this->service()->recentBattles($request->user()->id, 20);
    }

    public function createBattle(Request $request)
    {
        $data = $request->validate(['challenge_id' => ['required', 'string'], 'opponent_id' => ['required', 'string']]);
        $id = $this->service()->createBattle($data['challenge_id'], $request->user()->id, $data['opponent_id']);

        return response()->json(['id' => $id], 201);
    }

    public function battle(Request $request, string $id)
    {
        $s = $this->service();
        $battle = $s->battle($id);
        abort_if(! $battle, 404, 'Battle not found.');
        abort_unless(in_array($request->user()->id, [$battle['player1_id'], $battle['player2_id']], true), 403);

        return ['battle' => $battle, 'attempts' => $s->attemptsForBattle($id)];
    }

    public function submit(Request $request, string $id)
    {
        $data = $request->validate(['query' => ['required', 'string', 'max:3000'], 'battle_id' => ['nullable', 'string', 'max:64']]);

        return $this->service()->submit($id, $request->user()->id, $data['query'], $data['battle_id'] ?? null);
    }
}
