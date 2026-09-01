<?php

namespace App\Http\Controllers;

use App\Services\PerformanceProfileService;
use Illuminate\Http\Request;

class PerformanceProfileController extends Controller
{
    public function me(Request $request, PerformanceProfileService $service)
    {
        return $service->calculate($request->user()->id);
    }

    public function show(string $userId, PerformanceProfileService $service)
    {
        return $service->calculate($userId);
    }
}
