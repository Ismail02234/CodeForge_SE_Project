<?php

namespace App\Http\Controllers;

use App\Services\CodeDnaService;
use Illuminate\Http\Request;

class CodeDnaController extends Controller
{
    public function me(Request $request, CodeDnaService $service)
    {
        return $service->calculate($request->user()->id);
    }

    public function show(string $userId, CodeDnaService $service)
    {
        return $service->calculate($userId);
    }
}
