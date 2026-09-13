<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$steps = DB::table('learning_steps as s')
    ->where('s.learning_module_id', 'lm1')
    ->orderBy('s.step_order')
    ->select('s.id', 's.step_order', 's.type', 's.title', 's.content', 's.question', 's.options', 's.xp_reward')
    ->get()
    ->map(function ($s) {
        $options = [];
        if ($s->options) {
            $decoded = json_decode($s->options, true);
            if (is_array($decoded)) {
                $options = array_keys($decoded) !== range(0, count($decoded) - 1)
                    ? array_values($decoded)
                    : $decoded;
                $options = array_slice($options, 0, 5);
                if (count($options) < 2) {
                    $options = [];
                }
            }
        }
        $s->options = $options;
        unset($s->correct_answer);

        return $s;
    });

foreach ($steps as $s) {
    echo "Step: {$s->id} (type={$s->type})\n";
    if ($s->options) {
        echo "  options count: " . count($s->options) . "\n";
        echo "  options type: " . gettype($s->options) . "\n";
        echo "  first option: " . ($s->options[0] ?? 'N/A') . "\n";
    } else {
        echo "  options: null/empty\n";
    }
    echo "\n";
}

echo "\n=== JSON Response for MCQs ===\n";
$mcqSteps = $steps->filter(fn($s) => $s->type === 'mcq');
echo json_encode($mcqSteps, JSON_PRETTY_PRINT);
