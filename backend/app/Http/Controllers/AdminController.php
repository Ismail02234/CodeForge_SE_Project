<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Ids;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminController extends Controller
{
    private array $safeTables = [
        'users', 'universities', 'problems', 'submissions', 'problem_sessions', 'contests', 'contest_problems',
        'contest_participants', 'ghost_races', 'sql_challenges', 'sql_battles', 'sql_attempts', 'activity_logs',
        'arena_universities', 'arena_users', 'arena_problems', 'arena_submissions', 'topicstats',
    ];

    public function tables()
    {
        return collect($this->safeTables)->filter(fn ($table) => Schema::hasTable($table))->values();
    }

    public function rows(string $table, Request $request)
    {
        abort_unless(in_array($table, $this->safeTables, true) && Schema::hasTable($table), 404);
        $limit = max(1, min(100, (int) $request->query('limit', 50)));

        return DB::table($table)->limit($limit)->get();
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'rating' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'rank' => ['nullable', 'string', 'max:50'],
            'role' => ['nullable', Rule::in(['user', 'admin'])],
            'university' => ['nullable', 'string', Rule::exists('universities', 'name')],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'id' => Ids::make('u'),
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'user',
                'rating' => $data['rating'] ?? 1200,
                'university' => $data['university'] ?? null,
                'rank' => $data['rank'] ?? 'Newbie',
                'created_at' => now(),
            ]);

            DB::table('activity_logs')->insert([
                'user_id' => $user->id,
                'action' => 'admin.user_created',
                'details' => 'User created from database console',
                'created_at' => now(),
            ]);

            return $user;
        });

        return response()->json(['user' => $user], 201);
    }

    public function updateUser(string $id, Request $request)
    {
        abort_unless(DB::table('users')->where('id', $id)->exists(), 404, 'User not found.');

        $data = $request->validate([
            'rating' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'rank' => ['sometimes', 'string', 'max:50'],
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
            'university' => ['sometimes', 'nullable', Rule::exists('universities', 'name')],
        ]);

        if ($id === $request->user()->id && isset($data['role']) && $data['role'] !== 'admin') {
            throw new RuntimeException('You cannot remove your own administrator role here.');
        }

        DB::table('users')->where('id', $id)->update($data);

        return ['message' => 'User updated.'];
    }

    public function deleteUser(string $id, Request $request)
    {
        if ($id === $request->user()->id) {
            throw new RuntimeException('You cannot delete your own active account.');
        }

        $deleted = DB::transaction(fn () => DB::table('users')->where('id', $id)->delete());
        abort_if($deleted === 0, 404, 'User not found.');

        return ['message' => 'User deleted.'];
    }

    public function sqlLab(Request $request)
    {
        $query = trim((string) $request->validate(['query' => ['required', 'string', 'max:5000']])['query']);
        $sql = preg_replace('/;\s*$/', '', $query) ?? $query;

        if (str_contains($sql, ';') || ! preg_match('/^(SELECT|WITH|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', ltrim($sql))) {
            throw new RuntimeException('SQL Lab is read-only and accepts one SELECT/WITH/SHOW/DESCRIBE/EXPLAIN statement.');
        }

        $blocked = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'REPLACE',
            'GRANT', 'REVOKE', 'OUTFILE', 'DUMPFILE', 'LOAD_FILE', 'LOAD DATA', 'SLEEP', 'BENCHMARK',
        ];
        foreach ($blocked as $word) {
            if (stripos($sql, $word) !== false) {
                throw new RuntimeException("{$word} is blocked in SQL Lab.");
            }
        }

        $pdo = DB::connection()->getPdo();
        $timeoutMode = null;

        try {
            $pdo->exec('SET SESSION max_statement_time = 2');
            $timeoutMode = 'mariadb';
        } catch (\Throwable) {
            try {
                $pdo->exec('SET SESSION MAX_EXECUTION_TIME = 2000');
                $timeoutMode = 'mysql';
            } catch (\Throwable) {
                $timeoutMode = null;
            }
        }

        try {
            $stmt = $pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        } finally {
            try {
                if ($timeoutMode === 'mariadb') {
                    $pdo->exec('SET SESSION max_statement_time = 0');
                } elseif ($timeoutMode === 'mysql') {
                    $pdo->exec('SET SESSION MAX_EXECUTION_TIME = 0');
                }
            } catch (\Throwable) {
            }
        }

        if (count($rows) > 200) {
            $rows = array_slice($rows, 0, 200);
        }

        return ['rows' => $rows, 'count' => count($rows)];
    }
}
