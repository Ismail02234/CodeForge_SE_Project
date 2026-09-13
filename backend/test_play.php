<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check play challenges for lm1
$challenges = DB::table('play_challenges as c')
    ->where('c.learning_module_id', 'lm1')
    ->orderBy('c.id')
    ->select('c.id', 'c.type', 'c.title', 'c.instructions', 'c.config', 'c.xp_reward', 'c.time_limit')
    ->get()
    ->map(function ($c) {
        $config = [];
        if ($c->config) {
            $decoded = json_decode($c->config, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }
        $c->config = $config;

        return $c;
    });

echo "Play challenges for lm1:\n";
echo json_encode($challenges, JSON_PRETTY_PRINT);

// Check module id for binary-search-basics
$module = DB::table('learning_modules as m')
    ->where('m.slug', 'binary-search-basics')
    ->where('m.is_active', true)
    ->select('m.id', 'm.title', 'm.slug')
    ->first();

echo "\nModule: " . json_encode($module) . "\n";
