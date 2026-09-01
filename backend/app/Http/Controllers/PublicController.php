<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function stats()
    {
        return ['users' => DB::table('users')->count(), 'problems' => DB::table('problems')->count(), 'submissions' => DB::table('submissions')->count()];
    }

    public function universities()
    {
        return DB::table('universities')->orderBy('name')->get(['name', 'city']);
    }
}
