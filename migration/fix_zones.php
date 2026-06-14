<?php
/**
 * Zone Fix Utility - Menha Boutique PHP
 * The original migration used PHP || operator (returns bool) instead of ?: operator,
 * saving zone = '1' for every state. This script re-fetches zones from Supabase
 * and updates MySQL with the correct TN / SOUTH / REST / NE values.
 * Run once from browser or CLI.
 */

require_once __DIR__ . '/../config/db.php';

const SUPABASE_URL = 'https://wrjzdrhvrluamygexyvi.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indyanpkcmh2cmx1YW15Z2V4eXZpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzYzMjgwNjcsImV4cCI6MjA5MTkwNDA2N30.CQVMoSWZ1dDs0iFzpa9UjBTzRwW31ihjE-CMZcZwTBo';

if (php_sapi_name() !== 'cli') echo "<pre style='font-family:monospace;font-size:13px;padding:20px;'>";
echo "=== Zone Fix Utility ===\n\n";

$db = getDBConnection();

// Fetch states from Supabase
$url = SUPABASE_URL . "/rest/v1/states?select=id,zone";
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: '       . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
    ],
]);
$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200) {
    echo "ERROR: Supabase fetch failed (HTTP $code).\n";
    echo "Falling back to hardcoded Indian state zones...\n\n";

    // Hardcoded fallback: set zones by state name for India
    // TN = Tamil Nadu only
    // SOUTH = Kerala, Karnataka, Andhra Pradesh, Telangana, Puducherry, Goa
    // NE = Assam, Arunachal Pradesh, Manipur, Meghalaya, Mizoram, Nagaland, Sikkim, Tripura
    // REST = everything else

    $zoneMappings = [
        'TN'    => ['Tamil Nadu'],
        'SOUTH' => ['Kerala', 'Karnataka', 'Andhra Pradesh', 'Telangana', 'Puducherry', 'Goa'],
        'NE'    => ['Assam', 'Arunachal Pradesh', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Sikkim', 'Tripura'],
    ];

    $upd = $db->prepare("UPDATE states SET zone = ? WHERE name = ?");
    $rstUpd = $db->prepare("UPDATE states SET zone = 'REST' WHERE zone IS NULL OR zone = '' OR zone = '1' OR zone = '0'");

    // First set all unset/corrupt to REST
    $rstUpd->execute();
    echo "Set all corrupted zones → REST\n";

    // Then apply specific zones
    foreach ($zoneMappings as $zone => $stateNames) {
        foreach ($stateNames as $name) {
            $upd->execute([$zone, $name]);
            echo "  $name → $zone\n";
        }
    }

    echo "\nDone (hardcoded fallback).\n";
    if (php_sapi_name() !== 'cli') echo "</pre>";
    exit;
}

$states = json_decode($response, true) ?: [];
if (empty($states)) {
    echo "ERROR: No states returned from Supabase.\n";
    if (php_sapi_name() !== 'cli') echo "</pre>";
    exit;
}

echo "Fetched " . count($states) . " states from Supabase.\n\n";

$upd     = $db->prepare("UPDATE states SET zone = ? WHERE id = ?");
$updated = 0;
$skipped = 0;

foreach ($states as $s) {
    $zone = $s['zone'] ?: 'REST';  // PHP ?: not || (which returns bool)
    // Validate zone value — ignore if already a known valid zone stored correctly
    if (!in_array($zone, ['TN', 'SOUTH', 'REST', 'NE'])) {
        $zone = 'REST';
    }
    $upd->execute([$zone, $s['id']]);
    echo "  ID {$s['id']} → zone: $zone\n";
    $updated++;
}

echo "\n=== Done ===\n";
echo "Updated: $updated states\n";
echo "\nZone lookup will now work correctly for delivery charge calculation.\n";
echo "Delete this file after running: migration/fix_zones.php\n";

if (php_sapi_name() !== 'cli') echo "</pre>";
