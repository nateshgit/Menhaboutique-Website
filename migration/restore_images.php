<?php
/**
 * Image Restore Utility - Menha Boutique PHP
 * Re-fetches base64 images from Supabase and restores them to local storage.
 * Updates DB records with the new file paths.
 * Run once from browser or CLI after uploads/ folder is lost.
 */

require_once __DIR__ . '/../config/db.php';

const SUPABASE_URL = 'https://wrjzdrhvrluamygexyvi.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indyanpkcmh2cmx1YW15Z2V4eXZpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzYzMjgwNjcsImV4cCI6MjA5MTkwNDA2N30.CQVMoSWZ1dDs0iFzpa9UjBTzRwW31ihjE-CMZcZwTBo';

// All images restored here — single consistent directory for admin uploads too
$uploadBase = __DIR__ . '/../assets/images/uploads';

$dirs = [
    $uploadBase,
    $uploadBase . '/products',
    $uploadBase . '/categories',
    $uploadBase . '/banners',
];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) mkdir($dir, 0777, true);
}

if (php_sapi_name() !== 'cli') echo "<pre style='font-family:monospace;font-size:13px;padding:20px;'>";
echo "=== Menha Boutique — Image Restore ===\n\n";

$db = getDBConnection();
$restored = 0;
$skipped  = 0;
$failed   = 0;

// ── Helpers ────────────────────────────────────────────────────────────────

function fetchFromSupabase(string $table): array {
    $url = SUPABASE_URL . "/rest/v1/" . $table . "?select=*";
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
    if ($code !== 200) throw new Exception("Supabase fetch '$table' failed (HTTP $code): $response");
    return json_decode($response, true) ?: [];
}

/**
 * If $value is a base64 data URI, decode and save it.
 * If it's already an https:// URL, return as-is (image still lives on CDN).
 * If it's a local path that exists, return as-is.
 * Returns the path/URL to store in the DB.
 */
function restoreImage(string $value, string $subfolder): ?string {
    global $uploadBase, $restored, $skipped, $failed;

    if (empty($value)) return null;

    // Already an https:// URL — still accessible from CDN, keep it
    if (preg_match('#^https?://#', $value)) {
        echo "  [SKIP] External URL kept: " . substr($value, 0, 80) . "...\n";
        $skipped++;
        return $value;
    }

    // Local path that already exists on disk — nothing to do
    $absCheck = __DIR__ . '/../' . ltrim($value, '/');
    if (file_exists($absCheck)) {
        echo "  [SKIP] File already on disk: $value\n";
        $skipped++;
        return $value;
    }

    // Base64 data URI — decode and save
    if (preg_match('/^data:image\/(\w+);base64,/', $value, $m)) {
        $ext  = strtolower($m[1]);
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $ext = 'jpg';
        $data = base64_decode(substr($value, strpos($value, ',') + 1));
        if ($data === false) {
            echo "  [FAIL] base64 decode failed\n";
            $failed++;
            return null;
        }
        $filename = uniqid('img_', true) . '.' . $ext;
        $relPath  = 'assets/images/uploads/' . $subfolder . '/' . $filename;
        $absPath  = $uploadBase . '/' . $subfolder . '/' . $filename;
        file_put_contents($absPath, $data);
        echo "  [OK]   Saved $relPath\n";
        $restored++;
        return $relPath;
    }

    // Unrecognised — keep as-is
    echo "  [SKIP] Unknown format, keeping: " . substr($value, 0, 60) . "\n";
    $skipped++;
    return $value;
}

// ── 1. Banners ──────────────────────────────────────────────────────────────
echo "--- Banners ---\n";
try {
    $banners = fetchFromSupabase('banners');
    $upd = $db->prepare("UPDATE banners SET image_url = ? WHERE id = ?");
    foreach ($banners as $b) {
        if (empty($b['image_url'])) continue;
        $newPath = restoreImage($b['image_url'], 'banners');
        if ($newPath !== null) $upd->execute([$newPath, $b['id']]);
    }
    echo "Processed " . count($banners) . " banners.\n\n";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n\n"; }

// ── 2. Categories ───────────────────────────────────────────────────────────
echo "--- Categories ---\n";
try {
    $categories = fetchFromSupabase('categories');
    $upd = $db->prepare("UPDATE categories SET image = ? WHERE id = ?");
    foreach ($categories as $cat) {
        if (empty($cat['image'])) continue;
        $newPath = restoreImage($cat['image'], 'categories');
        if ($newPath !== null) $upd->execute([$newPath, $cat['id']]);
    }
    echo "Processed " . count($categories) . " categories.\n\n";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n\n"; }

// ── 3. Products (primary_image) ─────────────────────────────────────────────
echo "--- Products (primary_image) ---\n";
try {
    $products = fetchFromSupabase('products');
    $upd = $db->prepare("UPDATE products SET primary_image = ? WHERE id = ?");
    foreach ($products as $p) {
        if (empty($p['primary_image'])) continue;
        $newPath = restoreImage($p['primary_image'], 'products');
        if ($newPath !== null) $upd->execute([$newPath, $p['id']]);
    }
    echo "Processed " . count($products) . " products.\n\n";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n\n"; }

// ── 4. Product Images ───────────────────────────────────────────────────────
echo "--- Product Images ---\n";
try {
    $productImages = fetchFromSupabase('product_images');
    $upd = $db->prepare("UPDATE product_images SET image_url = ? WHERE id = ?");
    foreach ($productImages as $pi) {
        if (empty($pi['image_url'])) continue;
        $newPath = restoreImage($pi['image_url'], 'products');
        if ($newPath !== null) $upd->execute([$newPath, $pi['id']]);
    }
    echo "Processed " . count($productImages) . " product images.\n\n";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n\n"; }

// ── 5. Product Attributes (image_url) ───────────────────────────────────────
echo "--- Product Attributes ---\n";
try {
    $attrs = fetchFromSupabase('product_attributes');
    $upd = $db->prepare("UPDATE product_attributes SET image_url = ? WHERE id = ?");
    foreach ($attrs as $a) {
        if (empty($a['image_url'])) continue;
        $newPath = restoreImage($a['image_url'], 'products');
        if ($newPath !== null) $upd->execute([$newPath, $a['id']]);
    }
    echo "Processed " . count($attrs) . " product attributes.\n\n";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n\n"; }

// ── Summary ─────────────────────────────────────────────────────────────────
echo "=== Done ===\n";
echo "Restored : $restored images\n";
echo "Skipped  : $skipped (external URL or already exists)\n";
echo "Failed   : $failed\n\n";
echo "All restored images are in: assets/images/uploads/\n";
echo "DB records updated with new paths.\n";

if (php_sapi_name() !== 'cli') echo "</pre>";
