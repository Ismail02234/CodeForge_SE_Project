<?php

namespace App\Http\Controllers;

use App\Services\ProblemPracticeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProblemController extends Controller
{
    private function service(): ProblemPracticeService
    {
        return new ProblemPracticeService(DB::connection()->getPdo());
    }

    public function index(Request $request)
    {
        $uid = $request->user()->id;
        $q = DB::table('problems as p')->leftJoin('submissions as s', function ($j) use ($uid) {
            $j->on('s.problem_id', '=', 'p.id')->where('s.user_id', $uid)->where('s.verdict', 'AC');
        })->groupBy('p.id', 'p.title', 'p.topic', 'p.difficulty', 'p.description', 'p.tags', 'p.starter_code', 'p.created_at')
            ->selectRaw('p.*, COUNT(DISTINCT s.id) > 0 AS solved');

        if ($request->filled('topic')) {
            $q->where('p.topic', $request->string('topic'));
        }
        if ($request->filled('difficulty')) {
            $q->where('p.difficulty', $request->string('difficulty'));
        }
        if ($request->filled('q')) {
            $q->where(function ($x) use ($request) {
                $term = '%'.$request->string('q').'%';
                $x->where('p.title', 'like', $term)->orWhere('p.topic', 'like', $term)->orWhere('p.tags', 'like', $term);
            });
        }

        return ['problems' => $q->orderByRaw("FIELD(p.difficulty,'Easy','Medium','Hard')")->orderBy('p.id')->get(),
            'topics' => DB::table('problems')->distinct()->orderBy('topic')->pluck('topic')];
    }

    public function show(Request $request, string $id)
    {
        $service = $this->service();
        $problem = $service->getProblem($id);
        abort_if(! $problem, 404, 'Problem not found.');
        $session = $service->getOrCreateSession($request->user()->id, $id);

        return ['problem' => $problem, 'session' => $session, 'solved' => $service->hasSolved($request->user()->id, $id), 'attempts' => $service->sessionAttempts($session['id'])];
    }

    public function start(Request $request, string $id)
    {
        abort_unless($this->service()->getProblem($id), 404, 'Problem not found.');

        return response()->json($this->service()->getOrCreateSession($request->user()->id, $id), 201);
    }

    public function submit(Request $request, string $id)
    {
        $data = $request->validate(['source_code' => ['required', 'string', 'max:100000'], 'language' => ['required', 'string', 'max:30'], 'contest_id' => ['nullable', 'string', 'max:50']]);
        $service = $this->service();
        $session = $service->getOrCreateSession($request->user()->id, $id);

        return $service->submit($request->user()->id, $session['id'], $id, $data['source_code'], $data['language'], $data['contest_id'] ?? null);
    }
}
