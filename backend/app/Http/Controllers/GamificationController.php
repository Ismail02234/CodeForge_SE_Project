<?php

namespace App\Http\Controllers;

use App\Services\GamificationService;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function me(Request $request, GamificationService $service)
    {
        return $service->forUser($request->user()->id);
    }
}
