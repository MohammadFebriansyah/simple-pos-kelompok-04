<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;
use App\Models\Transaction;

DB::enableQueryLog();

$transactions = Transaction::latest()->paginate(15);

foreach ($transactions as $t) {
    foreach ($t->details as $d) {
        $d->product->name;
    }
}

$queryLog = DB::getQueryLog();
echo "Total query count: " . count($queryLog) . PHP_EOL;
echo PHP_EOL . "--- Query Log ---" . PHP_EOL;
foreach ($queryLog as $i => $q) {
    echo ($i + 1) . ". " . $q['query'] . PHP_EOL;
}
