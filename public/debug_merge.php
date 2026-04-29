<?php
// Debug merge candidates issue
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$db = $app->make('db');

// Check columns
echo "<h2>Contract status columns</h2><pre>";
$cols = $db->select("SHOW COLUMNS FROM contracts WHERE Field LIKE '%status%' OR Field = 'contract_type'");
foreach ($cols as $c) echo $c->Field . ": " . $c->Type . "\n";

echo "\n<b>Sample contracts (first 10):</b>\n";
$rows = $db->select("SELECT id, contract_number, contract_status, contract_type, customer_id, deleted_at FROM contracts LIMIT 10");
foreach ($rows as $r) {
    echo "#{$r->id} {$r->contract_number} | status={$r->contract_status} | type={$r->contract_type} | customer_id={$r->customer_id} | deleted=" . ($r->deleted_at ? 'YES' : 'no') . "\n";
}

echo "\n<b>Contracts with status IN (active, approved, signed):</b>\n";
$rows2 = $db->select("SELECT id, contract_number, contract_status, contract_type, customer_id FROM contracts WHERE contract_status IN ('active','approved','signed') AND deleted_at IS NULL ORDER BY id DESC LIMIT 15");
if (empty($rows2)) echo "TIDAK ADA!\n";
foreach ($rows2 as $r) {
    echo "#{$r->id} {$r->contract_number} | status={$r->contract_status} | type={$r->contract_type} | customer={$r->customer_id}\n";
}

echo "\n<b>Table contract_merges exists?</b>\n";
try {
    $db->select("SELECT COUNT(*) as c FROM contract_merges");
    echo "YES\n";
} catch (Exception $e) {
    echo "NO - " . $e->getMessage() . "\n";
}

echo "\n<b>All distinct contract_status values:</b>\n";
$statuses = $db->select("SELECT DISTINCT contract_status, COUNT(*) as c FROM contracts GROUP BY contract_status");
foreach ($statuses as $s) echo $s->contract_status . ": " . $s->c . "\n";

echo "</pre>";
