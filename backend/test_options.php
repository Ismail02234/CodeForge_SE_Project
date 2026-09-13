<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$row = DB::table('learning_steps')->where('id', 'dev_ls3')->first();
echo "Raw options: " . $row->options . PHP_EOL;
echo "Length: " . strlen($row->options) . PHP_EOL;
$decoded = json_decode($row->options, true);
echo "Decoded type: " . gettype($decoded) . PHP_EOL;
echo "Decoded count: " . (is_array($decoded) ? count($decoded) : 'N/A') . PHP_EOL;
var_dump($decoded);

echo "\n--- ls4 ---\n";
$row2 = DB::table('learning_steps')->where('id', 'ls4')->first();
echo "Raw options length: " . strlen($row2->options) . PHP_EOL;
$decoded2 = json_decode($row2->options, true);
echo "Decoded count: " . (is_array($decoded2) ? count($decoded2) : 'N/A') . PHP_EOL;
