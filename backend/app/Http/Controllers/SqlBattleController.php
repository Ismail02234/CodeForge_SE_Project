<?php

namespace App\Http\Controllers;

use App\Services\SqlBattleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

    public function opponents(Request $request)
    {
        $data = $request->validate([
            'challenge_id' => ['nullable', 'string', 'exists:sql_challenges,id'],
        ]);

        return $this->service()->opponents(
            (string) $request->user()->id,
            $data['challenge_id'] ?? null
        );
    }

    public function leaderboard()
    {
        return $this->service()->leaderboard(20);
    }

    public function recent(Request $request)
    {
        return $this->service()->recentBattles((string) $request->user()->id, 20);
    }

    public function createBattle(Request $request)
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string', 'exists:sql_challenges,id'],
            'opponent_id' => ['required', 'string', 'exists:users,id'],
        ]);

        try {
            $battle = $this->service()->createBattle(
                $data['challenge_id'],
                (string) $request->user()->id,
                $data['opponent_id']
            );
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }

        return response()->json($battle, $battle['created'] ? 201 : 200);
    }

    public function battle(Request $request, string $id)
    {
        $service = $this->service();
        $battle = $service->battle($id);

        abort_if(! $battle, 404, 'Battle not found.');

        abort_unless(
            in_array(
                (string) $request->user()->id,
                [(string) $battle['player1_id'], (string) $battle['player2_id']],
                true
            ),
            403
        );

        return [
            'current_user_id' => (string) $request->user()->id,
            'battle' => $battle,
            'attempts' => $service->attemptsForBattle($id),
        ];
    }

    public function submit(Request $request, string $id)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:3000'],
            'battle_id' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            return $this->service()->submit(
                $id,
                (string) $request->user()->id,
                $data['query'],
                $data['battle_id'] ?? null
            );
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }
    }
}
