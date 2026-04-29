<?php
// Capture the rendered HTML output and find line 10008
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/operational/job-assign-material-issues?page=1', 'GET');
$response = $kernel->handle($request);

$content = $response->getContent();
$lines = explode("\n", $content);

echo "Total lines: " . count($lines) . "\n\n";
echo "Lines 10003-10013:\n";
for ($i = 10002; $i < min(10013, count($lines)); $i++) {
    echo "Line " . ($i + 1) . ": " . htmlspecialchars(substr($lines[$i], 0, 200)) . "\n";
}
