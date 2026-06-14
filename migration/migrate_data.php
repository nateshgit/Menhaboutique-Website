<?php
/**
 * Data Migration Utility - Supabase to MySQL (Menha Boutique)
 * Decodes base64 images into physical files and seeds local MySQL database.
 */

require_once __DIR__ . '/../config/db.php';

const SUPABASE_URL = 'https://wrjzdrhvrluamygexyvi.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indyanpkcmh2cmx1YW15Z2V4eXZpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzYzMjgwNjcsImV4cCI6MjA5MTkwNDA2N30.CQVMoSWZ1dDs0iFzpa9UjBTzRwW31ihjE-CMZcZwTBo';

// Create uploads subdirectories
$dirs = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/products',
    __DIR__ . '/../uploads/categories',
    __DIR__ . '/../uploads/banners'
];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

$db = getDBConnection();

function fetchFromSupabase($table) {
    $url = SUPABASE_URL . "/rest/v1/" . $table . "?select=*";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Failed to fetch $table from Supabase. Code: $httpCode. Response: $response");
    }

    return json_decode($response, true);
}

// Helper to save base64 string to a file and return relative path
function processImage($imageField, $subfolder) {
    if (empty($imageField)) {
        return null;
    }

    // Check if image is base64 string
    if (preg_match('/^data:image\/(\w+);base64,/', $imageField, $type)) {
        $data = substr($imageField, strpos($imageField, ',') + 1);
        $data = base64_decode($data);
        if ($data === false) {
            return $imageField; // fallback if decode fails
        }
        
        $ext = strtolower($type[1]);
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $ext = 'jpg';
        }
        
        $filename = uniqid('img_', true) . '.' . $ext;
        $relPath = 'uploads/' . $subfolder . '/' . $filename;
        $absPath = __DIR__ . '/../' . $relPath;
        
        file_put_contents($absPath, $data);
        echo "Saved base64 image as file: $relPath\n";
        return $relPath;
    }

    // If it's already a URL or path, keep it as is
    return $imageField;
}

try {
    if (php_sapi_name() !== 'cli') {
        echo "<pre>";
    }
    echo "Starting migration...\n\n";

    // 1. Countries
    echo "Fetching countries...\n";
    $countries = fetchFromSupabase('countries');
    $stmt = $db->prepare("INSERT INTO countries (id, name, code) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), code=VALUES(code)");
    foreach ($countries as $c) {
        $stmt->execute([$c['id'], $c['name'], $c['code']]);
    }
    echo "Migrated " . count($countries) . " countries.\n\n";

    // 2. States
    echo "Fetching states...\n";
    $states = fetchFromSupabase('states');
    $stmt = $db->prepare("INSERT INTO states (id, country_id, name, code, zone) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), code=VALUES(code), zone=VALUES(zone)");
    foreach ($states as $s) {
        $stmt->execute([$s['id'], $s['country_id'], $s['name'], $s['code'], $s['zone'] ?: 'REST']);
    }
    echo "Migrated " . count($states) . " states.\n\n";

    // 3. Cities
    echo "Fetching cities...\n";
    $cities = fetchFromSupabase('cities');
    $stmt = $db->prepare("INSERT INTO cities (id, state_id, name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
    foreach ($cities as $ci) {
        $stmt->execute([$ci['id'], $ci['state_id'], $ci['name']]);
    }
    echo "Migrated " . count($cities) . " cities.\n\n";

    // 4. Brands (if any exist)
    echo "Fetching brands...\n";
    try {
        $brands = fetchFromSupabase('brands');
        $stmt = $db->prepare("INSERT INTO brands (id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
        foreach ($brands as $b) {
            $stmt->execute([$b['id'], $b['name']]);
        }
        echo "Migrated " . count($brands) . " brands.\n\n";
    } catch (Exception $ex) {
        echo "No brands table or migration skipped: " . $ex->getMessage() . "\n\n";
    }

    // 5. Users
    echo "Fetching users...\n";
    $users = fetchFromSupabase('users');
    $stmt = $db->prepare("INSERT INTO users (id, email, password_hash, first_name, last_name, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE email=VALUES(email), password_hash=VALUES(password_hash), first_name=VALUES(first_name), last_name=VALUES(last_name), phone_number=VALUES(phone_number), role=VALUES(role)");
    foreach ($users as $u) {
        $stmt->execute([$u['id'], $u['email'], $u['password_hash'], $u['first_name'], $u['last_name'], $u['phone_number'], $u['role']]);
    }
    echo "Migrated " . count($users) . " users.\n\n";

    // 6. Categories
    echo "Fetching categories...\n";
    $categories = fetchFromSupabase('categories');
    $stmt = $db->prepare("INSERT INTO categories (id, name, slug, description, image, sequence, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), slug=VALUES(slug), description=VALUES(description), image=VALUES(image), sequence=VALUES(sequence), parent_id=VALUES(parent_id)");
    foreach ($categories as $cat) {
        $imagePath = processImage($cat['image'], 'categories');
        $stmt->execute([$cat['id'], $cat['name'], $cat['slug'], $cat['description'], $imagePath, $cat['sequence'] || 0, $cat['parent_id']]);
    }
    echo "Migrated " . count($categories) . " categories.\n\n";

    // 7. Products
    echo "Fetching products...\n";
    $products = fetchFromSupabase('products');
    $stmt = $db->prepare("INSERT INTO products (id, title, sku, description, category_id, brand_id, new_price, old_price, weight, stock_quantity, status, sequence, sale_tag, rating, primary_image, is_special, is_combo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), sku=VALUES(sku), description=VALUES(description), category_id=VALUES(category_id), brand_id=VALUES(brand_id), new_price=VALUES(new_price), old_price=VALUES(old_price), weight=VALUES(weight), stock_quantity=VALUES(stock_quantity), status=VALUES(status), sequence=VALUES(sequence), sale_tag=VALUES(sale_tag), rating=VALUES(rating), primary_image=VALUES(primary_image), is_special=VALUES(is_special), is_combo=VALUES(is_combo)");
    foreach ($products as $p) {
        $primaryImage = processImage($p['primary_image'], 'products');
        $stmt->execute([
            $p['id'],
            $p['title'],
            $p['sku'],
            $p['description'],
            $p['category_id'],
            $p['brand_id'],
            $p['new_price'],
            $p['old_price'],
            $p['weight'],
            $p['stock_quantity'] || 0,
            $p['status'] || 'In Stock',
            $p['sequence'] || 0,
            $p['sale_tag'],
            $p['rating'] || 0.0,
            $primaryImage,
            $p['is_special'] ? 1 : 0,
            $p['is_combo'] ? 1 : 0
        ]);
    }
    echo "Migrated " . count($products) . " products.\n\n";

    // 8. Product Images
    echo "Fetching product images...\n";
    try {
        $productImages = fetchFromSupabase('product_images');
        $stmt = $db->prepare("INSERT INTO product_images (id, product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE image_url=VALUES(image_url), is_primary=VALUES(is_primary), display_order=VALUES(display_order)");
        foreach ($productImages as $pi) {
            $imageUrl = processImage($pi['image_url'], 'products');
            $stmt->execute([
                $pi['id'],
                $pi['product_id'],
                $imageUrl,
                $pi['is_primary'] ? 1 : 0,
                $pi['display_order'] || 0
            ]);
        }
        echo "Migrated " . count($productImages) . " product images.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty product_images: " . $ex->getMessage() . "\n\n";
    }

    // 9. Product Attributes (Variants)
    echo "Fetching product attributes...\n";
    try {
        $productAttributes = fetchFromSupabase('product_attributes');
        $stmt = $db->prepare("INSERT INTO product_attributes (id, product_id, attribute_type, attribute_value, price, old_price, stock_quantity, is_default, display_order, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE attribute_type=VALUES(attribute_type), attribute_value=VALUES(attribute_value), price=VALUES(price), old_price=VALUES(old_price), stock_quantity=VALUES(stock_quantity), is_default=VALUES(is_default), display_order=VALUES(display_order), image_url=VALUES(image_url)");
        foreach ($productAttributes as $pa) {
            $imageUrl = processImage($pa['image_url'], 'products');
            $stmt->execute([
                $pa['id'],
                $pa['product_id'],
                $pa['attribute_type'] || 'weight',
                $pa['attribute_value'],
                $pa['price'],
                $pa['old_price'],
                $pa['stock_quantity'] || 0,
                $pa['is_default'] ? 1 : 0,
                $pa['display_order'] || 0,
                $imageUrl
            ]);
        }
        echo "Migrated " . count($productAttributes) . " product attributes.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty product_attributes: " . $ex->getMessage() . "\n\n";
    }

    // 10. Banners
    echo "Fetching banners...\n";
    try {
        $banners = fetchFromSupabase('banners');
        $stmt = $db->prepare("INSERT INTO banners (id, image_url, link_url, is_active, sequence) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE image_url=VALUES(image_url), link_url=VALUES(link_url), is_active=VALUES(is_active), sequence=VALUES(sequence)");
        foreach ($banners as $b) {
            $imageUrl = processImage($b['image_url'], 'banners');
            $stmt->execute([
                $b['id'],
                $imageUrl,
                $b['link_url'],
                $b['is_active'] ? 1 : 0,
                $b['sequence'] || 0
            ]);
        }
        echo "Migrated " . count($banners) . " banners.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty banners: " . $ex->getMessage() . "\n\n";
    }

    // 11. Couriers
    echo "Fetching couriers...\n";
    try {
        $couriers = fetchFromSupabase('couriers');
        $stmt = $db->prepare("INSERT INTO couriers (id, name, is_active) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), is_active=VALUES(is_active)");
        foreach ($couriers as $cr) {
            $stmt->execute([$cr['id'], $cr['name'], $cr['is_active'] ? 1 : 0]);
        }
        echo "Migrated " . count($couriers) . " couriers.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty couriers: " . $ex->getMessage() . "\n\n";
    }

    // 12. Payment Gateways
    echo "Fetching payment gateways...\n";
    try {
        $gateways = fetchFromSupabase('payment_gateways');
        $stmt = $db->prepare("INSERT INTO payment_gateways (id, name, type, credentials, is_active, is_test_mode) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), type=VALUES(type), credentials=VALUES(credentials), is_active=VALUES(is_active), is_test_mode=VALUES(is_test_mode)");
        foreach ($gateways as $gw) {
            $creds = is_array($gw['credentials']) ? json_encode($gw['credentials']) : $gw['credentials'];
            $stmt->execute([
                $gw['id'],
                $gw['name'],
                $gw['type'],
                $creds,
                $gw['is_active'] ? 1 : 0,
                $gw['is_test_mode'] ? 1 : 0
            ]);
        }
        echo "Migrated " . count($gateways) . " payment gateways.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty payment_gateways: " . $ex->getMessage() . "\n\n";
    }

    // 13. Delivery Config
    echo "Fetching delivery config...\n";
    try {
        $config = fetchFromSupabase('delivery_config');
        $stmt = $db->prepare("INSERT INTO delivery_config (id, calculation_mode) VALUES (?, ?) ON DUPLICATE KEY UPDATE calculation_mode=VALUES(calculation_mode)");
        foreach ($config as $cfg) {
            $stmt->execute([$cfg['id'], $cfg['calculation_mode']]);
        }
        echo "Migrated " . count($config) . " delivery configs.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty delivery_config: " . $ex->getMessage() . "\n\n";
    }

    // 14. Delivery Tariffs
    echo "Fetching delivery tariffs...\n";
    try {
        $tariffs = fetchFromSupabase('delivery_tariffs');
        $stmt = $db->prepare("INSERT INTO delivery_tariffs (id, max_weight, prices, tariff_type) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_weight=VALUES(max_weight), prices=VALUES(prices), tariff_type=VALUES(tariff_type)");
        foreach ($tariffs as $tr) {
            $prices = is_array($tr['prices']) ? json_encode($tr['prices']) : $tr['prices'];
            $stmt->execute([
                $tr['id'],
                $tr['max_weight'],
                $prices,
                $tr['tariff_type']
            ]);
        }
        echo "Migrated " . count($tariffs) . " tariffs.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty delivery_tariffs: " . $ex->getMessage() . "\n\n";
    }

    // 15. Addresses
    echo "Fetching addresses...\n";
    try {
        $addresses = fetchFromSupabase('addresses');
        $stmt = $db->prepare("INSERT INTO addresses (id, user_id, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone_number, alternate_phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), address_line1=VALUES(address_line1), address_line2=VALUES(address_line2), city=VALUES(city), state=VALUES(state), zip_code=VALUES(zip_code), country=VALUES(country), phone_number=VALUES(phone_number), alternate_phone=VALUES(alternate_phone), is_default=VALUES(is_default)");
        foreach ($addresses as $addr) {
            $stmt->execute([
                $addr['id'],
                $addr['user_id'],
                $addr['first_name'],
                $addr['last_name'],
                $addr['address_line1'],
                $addr['address_line2'],
                $addr['city'],
                $addr['state'],
                $addr['zip_code'],
                $addr['country'] || 'India',
                $addr['phone_number'],
                $addr['alternate_phone'],
                $addr['is_default'] ? 1 : 0
            ]);
        }
        echo "Migrated " . count($addresses) . " addresses.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty addresses: " . $ex->getMessage() . "\n\n";
    }

    // 16. Product Reviews
    echo "Fetching product reviews...\n";
    try {
        $reviews = fetchFromSupabase('product_reviews');
        $stmt = $db->prepare("INSERT INTO product_reviews (id, product_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)");
        foreach ($reviews as $rev) {
            $stmt->execute([
                $rev['id'],
                $rev['product_id'],
                $rev['user_id'],
                $rev['rating'],
                $rev['comment']
            ]);
        }
        echo "Migrated " . count($reviews) . " product reviews.\n\n";
    } catch (Exception $ex) {
        echo "Error or empty product_reviews: " . $ex->getMessage() . "\n\n";
    }

    echo "Migration successfully completed!\n";
    if (php_sapi_name() !== 'cli') {
        echo "</pre>";
    }

} catch (Exception $e) {
    echo "\nMigration failed with error: " . $e->getMessage() . "\n";
    if (php_sapi_name() !== 'cli') {
        echo "</pre>";
    }
}
