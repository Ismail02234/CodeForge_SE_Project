<?php

namespace App\Http\Controllers;

use App\Services\GhostRaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GhostRaceController extends Controller
{
    private function service(): GhostRaceService
    {
        return new GhostRaceService(DB::connection()->getPdo());
    }

    public function options(Request $request)
    {
        return $this->service()->availableGhosts($request->user()->id);
    }

    public function history(Request $request)
    {
        return $this->service()->history($request->user()->id, 12);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['ghost_session_id' => ['required', 'string', 'max:64'], 'playback_speed' => ['nullable', 'integer', 'min:1', 'max:10']]);
        $id = $this->service()->createRace($request->user()->id, $data['ghost_session_id'], (int) ($data['playback_speed'] ?? 4));

        return response()->json(['id' => $id], 201);
    }

    public function show(Request $request, string $id)
    {
        $service = $this->service();
        $race = $service->getRace($id, $request->user()->id);
        abort_if(! $race, 404, 'Race not found.');

        return ['race' => $race, 'ghost_events' => $service->ghostEvents($race['ghost_session_id']),
            'challenger_events' => $service->challengerEvents($race['challenger_session_id']), 'virtual_elapsed' => $service->virtualElapsed($race)];
    }

    public function submit(Request $request, string $id)
    {
        $data = $request->validate(['source_code' => ['required', 'string', 'max:100000'], 'language' => ['required', 'string', 'max:30']]);

        return $this->service()->submit($id, $request->user()->id, $data['source_code'], $data['language']);
    }

    public function forfeit(Request $request, string $id)
    {
        $this->service()->forfeit($id, $request->user()->id);

        return ['message' => 'Race forfeited.'];
    }
}
