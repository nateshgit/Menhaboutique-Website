<?php
/**
 * Supabase-to-MySQL Shim API - Menha Boutique PHP
 * Translates PostgREST query parameters and actions into MySQL PDO queries.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();

// Self-healing database migration for wishlists table
try {
    $pdo->query("SELECT 1 FROM `wishlists` LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `wishlists` (
            `id` VARCHAR(36) PRIMARY KEY,
            `user_id` VARCHAR(36) NOT NULL,
            `product_id` VARCHAR(36) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `user_product_unique` (`user_id`, `product_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    } catch (Exception $ex) {
        // Fail silently
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// Get table parameter
$table = isset($_GET['table']) ? trim($_GET['table']) : '';
if (empty($table)) {
    // If not in query, check URL path
    $pathInfo = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
    if (empty($pathInfo)) {
        $requestUri = explode('?', $_SERVER['REQUEST_URI'])[0];
        $parts = explode('/supabase_shim.php/', $requestUri);
        if (count($parts) > 1) {
            $pathInfo = $parts[1];
        }
    }
    $table = explode('/', $pathInfo)[0];
}

// Strip query parameters to avoid SQL injection on table name
$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

if (empty($table)) {
    http_response_code(400);
    echo json_encode(['error' => 'Table name is required']);
    exit;
}

// ── Auth Check & Row-Level Security ──
$currentUser = getCurrentUser();
$currentUserId = $currentUser ? $currentUser['id'] : null;
$isAdmin = isAdmin();

// Restrict write methods for all tables except user-writable ones
$isWriteMethod = in_array($method, ['POST', 'PATCH', 'DELETE']);
$userAllowedWriteTables = ['contact_messages', 'carts', 'cart_items', 'addresses', 'orders', 'product_reviews', 'wishlists'];
$adminOnlyReadTables = ['payment_gateways', 'delivery_config', 'order_prefix'];

if ($isWriteMethod) {
    if (!in_array($table, $userAllowedWriteTables)) {
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin write access required']);
            exit;
        }
    }
} else {
    // GET request
    if (in_array($table, $adminOnlyReadTables)) {
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin read access required']);
            exit;
        }
    }
}

// User-level table authentication enforcement
if (in_array($table, ['addresses', 'orders', 'carts', 'cart_items', 'wishlists'])) {
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: Logged-in user required']);
        exit;
    }
}

// ── Parsing WHERE Filters ──
$whereClauses = [];
$params = [];

foreach ($_GET as $key => $val) {
    if (in_array($key, ['select', 'order', 'table'])) {
        continue;
    }
    
    // Support table name prefix in request mapping
    if ($key === 'or') {
        // e.g. or=(status.eq.pending,status.eq.processing)
        $val = trim($val, '()');
        $clauses = explode(',', $val);
        $orParts = [];
        foreach ($clauses as $idx => $clause) {
            $parts = explode('.', $clause, 3);
            if (count($parts) >= 2) {
                $col = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[0]);
                $op = $parts[1];
                $subVal = isset($parts[2]) ? urldecode($parts[2]) : '';
                $paramName = "or_{$col}_{$idx}";
                
                if ($op === 'eq') {
                    $orParts[] = "`$col` = :$paramName";
                    $params[$paramName] = $subVal;
                } elseif ($op === 'is' && $subVal === 'null') {
                    $orParts[] = "`$col` IS NULL";
                }
            }
        }
        if (!empty($orParts)) {
            $whereClauses[] = "(" . implode(" OR ", $orParts) . ")";
        }
    } else {
        $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
        if (empty($cleanCol)) continue;
        
        $parts = explode('.', $val, 2);
        $op = $parts[0];
        $subVal = isset($parts[1]) ? urldecode($parts[1]) : '';
        $paramName = "where_" . $cleanCol;
        
        if ($op === 'eq') {
            if ($subVal === 'is.null') {
                $whereClauses[] = "`$cleanCol` IS NULL";
            } else {
                if ($subVal === 'true')  $subVal = 1;
                if ($subVal === 'false') $subVal = 0;
                $whereClauses[] = "`$cleanCol` = :$paramName";
                $params[$paramName] = $subVal;
            }
        } elseif ($op === 'is' && $subVal === 'null') {
            $whereClauses[] = "`$cleanCol` IS NULL";
        } elseif ($op === 'ilike') {
            $whereClauses[] = "`$cleanCol` LIKE :$paramName";
            $params[$paramName] = '%' . $subVal . '%';
        } elseif ($op === 'gt') {
            $whereClauses[] = "`$cleanCol` > :$paramName";
            $params[$paramName] = $subVal;
        } elseif ($op === 'lt') {
            $whereClauses[] = "`$cleanCol` < :$paramName";
            $params[$paramName] = $subVal;
        } elseif ($op === 'neq') {
            $whereClauses[] = "`$cleanCol` <> :$paramName";
            $params[$paramName] = $subVal;
        }
    }
}

// Enforce user-level read/edit isolation (always isolate wishlists and carts; isolate addresses and orders for non-admins)
if (in_array($table, ['wishlists', 'carts'])) {
    $whereClauses[] = "`user_id` = :auth_user_id";
    $params['auth_user_id'] = $currentUserId;
} elseif (!$isAdmin && in_array($table, ['addresses', 'orders'])) {
    $whereClauses[] = "`user_id` = :auth_user_id";
    $params['auth_user_id'] = $currentUserId;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
}

// ── GET Method (SELECT) ──
if ($method === 'GET') {
    $select = isset($_GET['select']) ? $_GET['select'] : '*';
    
    // Sort / Ordering
    $orderBy = '';
    if (isset($_GET['order'])) {
        $orderParts = explode('.', $_GET['order']);
        $orderCol = preg_replace('/[^a-zA-Z0-9_]/', '', $orderParts[0]);
        $orderDir = isset($orderParts[1]) && strtolower($orderParts[1]) === 'desc' ? 'DESC' : 'ASC';
        if (!empty($orderCol)) {
            $orderBy = " ORDER BY `$orderCol` $orderDir";
        }
    }
    
    try {
        $queryStr = "SELECT * FROM `$table`" . $whereSql . $orderBy;
        $stmt = $pdo->prepare($queryStr);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Auto-decode JSON string columns (e.g. prices, credentials) back into objects
        foreach ($rows as &$row) {
            foreach ($row as &$v) {
                if (is_string($v) && strlen($v) > 1 && ($v[0] === '{' || $v[0] === '[')) {
                    $decoded = json_decode($v, true);
                    if (json_last_error() === JSON_ERROR_NONE) $v = $decoded;
                }
            }
        }
        unset($row, $v);

        // Handle Joins & Relations
        if (!empty($rows)) {
            // 1. Join product_attributes on products
            if ($table === 'products' && strpos($select, 'product_attributes') !== false) {
                $productIds = array_column($rows, 'id');
                if (!empty($productIds)) {
                    $inQuery = implode(',', array_fill(0, count($productIds), '?'));
                    $attrStmt = $pdo->prepare("SELECT * FROM product_attributes WHERE product_id IN ($inQuery) ORDER BY display_order ASC");
                    $attrStmt->execute($productIds);
                    $attrs = $attrStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $attrsByProduct = [];
                    foreach ($attrs as $attr) {
                        $attrsByProduct[$attr['product_id']][] = $attr;
                    }
                    
                    foreach ($rows as &$row) {
                        $row['product_attributes'] = isset($attrsByProduct[$row['id']]) ? $attrsByProduct[$row['id']] : [];
                    }
                }
            }
            
            // 2. Join users on product_reviews
            if ($table === 'product_reviews' && strpos($select, 'users') !== false) {
                $userIds = array_values(array_unique(array_column($rows, 'user_id')));
                if (!empty($userIds)) {
                    $inQuery = implode(',', array_fill(0, count($userIds), '?'));
                    $userStmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id IN ($inQuery)");
                    $userStmt->execute($userIds);
                    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $usersById = [];
                    foreach ($users as $u) {
                        $usersById[$u['id']] = $u;
                    }
                    
                    foreach ($rows as &$row) {
                        $row['users'] = isset($usersById[$row['user_id']]) ? $usersById[$row['user_id']] : null;
                    }
                }
            }
            
            // Join products on product_reviews
            if ($table === 'product_reviews' && strpos($select, 'products') !== false) {
                $productIds = array_values(array_unique(array_column($rows, 'product_id')));
                if (!empty($productIds)) {
                    $inQuery = implode(',', array_fill(0, count($productIds), '?'));
                    $prodStmt = $pdo->prepare("SELECT id, title FROM products WHERE id IN ($inQuery)");
                    $prodStmt->execute($productIds);
                    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $productsById = [];
                    foreach ($products as $p) {
                        $productsById[$p['id']] = $p;
                    }
                    
                    foreach ($rows as &$row) {
                        $row['products'] = isset($productsById[$row['product_id']]) ? $productsById[$row['product_id']] : null;
                    }
                }
            }

            // Join products on wishlists
            if ($table === 'wishlists' && (strpos($select, 'product') !== false || strpos($select, 'products') !== false)) {
                $productIds = array_values(array_unique(array_column($rows, 'product_id')));
                if (!empty($productIds)) {
                    $inQuery = implode(',', array_fill(0, count($productIds), '?'));
                    $prodStmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($inQuery)");
                    $prodStmt->execute($productIds);
                    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $productsById = [];
                    foreach ($products as $p) {
                        $productsById[$p['id']] = $p;
                    }
                    
                    foreach ($rows as &$row) {
                        $row['product'] = isset($productsById[$row['product_id']]) ? $productsById[$row['product_id']] : null;
                        $row['products'] = $row['product'];
                    }
                }
            }

            // 3. Join countries on states
            if ($table === 'states' && strpos($select, 'countries') !== false) {
                $countryIds = array_values(array_unique(array_column($rows, 'country_id')));
                if (!empty($countryIds)) {
                    $inQuery = implode(',', array_fill(0, count($countryIds), '?'));
                    $countryStmt = $pdo->prepare("SELECT id, name FROM countries WHERE id IN ($inQuery)");
                    $countryStmt->execute($countryIds);
                    $countries = $countryStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $countriesById = [];
                    foreach ($countries as $c) {
                        $countriesById[$c['id']] = $c;
                    }
                    
                    foreach ($rows as &$row) {
                        $row['countries'] = isset($countriesById[$row['country_id']]) ? $countriesById[$row['country_id']] : null;
                    }
                }
            }

            // 4. Join states on cities
            if ($table === 'cities' && strpos($select, 'states') !== false) {
                $stateIds = array_values(array_unique(array_column($rows, 'state_id')));
                if (!empty($stateIds)) {
                    $inQuery = implode(',', array_fill(0, count($stateIds), '?'));
                    $stateStmt = $pdo->prepare("SELECT id, name FROM states WHERE id IN ($inQuery)");
                    $stateStmt->execute($stateIds);
                    $states = $stateStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $statesById = [];
                    foreach ($states as $s) {
                        $statesById[$s['id']] = $s;
                    }
                    
                    foreach ($rows as &$row) {
                        $row['states'] = isset($statesById[$row['state_id']]) ? $statesById[$row['state_id']] : null;
                    }
                }
            }

            // 5. Join products on cart_items
            if ($table === 'cart_items' && (strpos($select, 'product') !== false)) {
                $productIds = array_values(array_unique(array_column($rows, 'product_id')));
                if (!empty($productIds)) {
                    $inQuery = implode(',', array_fill(0, count($productIds), '?'));
                    $prodStmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($inQuery)");
                    $prodStmt->execute($productIds);
                    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Fetch attributes for these products
                    $attrStmt = $pdo->prepare("SELECT * FROM product_attributes WHERE product_id IN ($inQuery) ORDER BY display_order ASC");
                    $attrStmt->execute($productIds);
                    $attrs = $attrStmt->fetchAll(PDO::FETCH_ASSOC);
                    $attrsByProduct = [];
                    foreach ($attrs as $attr) {
                        $attrsByProduct[$attr['product_id']][] = $attr;
                    }

                    $productsById = [];
                    foreach ($products as $p) {
                        $p['product_attributes'] = isset($attrsByProduct[$p['id']]) ? $attrsByProduct[$p['id']] : [];
                        $productsById[$p['id']] = $p;
                    }
                    
                    foreach ($rows as &$row) {
                        $row['product'] = isset($productsById[$row['product_id']]) ? $productsById[$row['product_id']] : null;
                    }
                }
            }
            
            // Join products on wishlists
            if ($table === 'wishlists' && (strpos($select, 'product') !== false)) {
                $productIds = array_values(array_unique(array_column($rows, 'product_id')));
                if (!empty($productIds)) {
                    $inQuery = implode(',', array_fill(0, count($productIds), '?'));
                    $prodStmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($inQuery)");
                    $prodStmt->execute($productIds);
                    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Fetch attributes for these products
                    $attrStmt = $pdo->prepare("SELECT * FROM product_attributes WHERE product_id IN ($inQuery) ORDER BY display_order ASC");
                    $attrStmt->execute($productIds);
                    $attrs = $attrStmt->fetchAll(PDO::FETCH_ASSOC);
                    $attrsByProduct = [];
                    foreach ($attrs as $attr) {
                        $attrsByProduct[$attr['product_id']][] = $attr;
                    }
                    
                    $productsById = [];
                    foreach ($products as $p) {
                        $p['product_attributes'] = isset($attrsByProduct[$p['id']]) ? $attrsByProduct[$p['id']] : [];
                        $productsById[$p['id']] = $p;
                    }
                    
                    foreach ($rows as &$row) {
                        $row['product'] = isset($productsById[$row['product_id']]) ? $productsById[$row['product_id']] : null;
                    }
                }
            }

            // 6. Join order_items and addresses on orders
            if ($table === 'orders') {
                $orderIds = array_column($rows, 'id');
                if (!empty($orderIds)) {
                    $inQuery = implode(',', array_fill(0, count($orderIds), '?'));
                    
                    // Join order_items if requested
                    if (strpos($select, 'order_items') !== false) {
                        $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($inQuery)");
                        $itemsStmt->execute($orderIds);
                        $allItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // If products details are requested inside order_items
                        $productsById = [];
                        if (strpos($select, 'products') !== false) {
                            $productIds = array_values(array_unique(array_filter(array_column($allItems, 'product_id'))));
                            if (!empty($productIds)) {
                                $pInQuery = implode(',', array_fill(0, count($productIds), '?'));
                                $pStmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($pInQuery)");
                                $pStmt->execute($productIds);
                                $products = $pStmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($products as $p) {
                                    $productsById[$p['id']] = $p;
                                }
                            }
                        }
                        
                        $itemsByOrder = [];
                        foreach ($allItems as $item) {
                            if (isset($productsById[$item['product_id']])) {
                                $item['products'] = $productsById[$item['product_id']];
                                $item['product'] = $productsById[$item['product_id']];
                            }
                            $itemsByOrder[$item['order_id']][] = $item;
                        }
                        
                        foreach ($rows as &$row) {
                            $row['order_items'] = isset($itemsByOrder[$row['id']]) ? $itemsByOrder[$row['id']] : [];
                            $row['items'] = isset($itemsByOrder[$row['id']]) ? $itemsByOrder[$row['id']] : [];
                        }
                        unset($row);
                    }
                    
                    // Join addresses if requested
                    if (strpos($select, 'addresses') !== false) {
                        $addressIds = array_values(array_unique(array_filter(array_column($rows, 'address_id'))));
                        if (!empty($addressIds)) {
                            $addrInQuery = implode(',', array_fill(0, count($addressIds), '?'));
                            $addrStmt = $pdo->prepare("SELECT * FROM addresses WHERE id IN ($addrInQuery)");
                            $addrStmt->execute($addressIds);
                            $addresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC);
                            $addressesById = [];
                            foreach ($addresses as $addr) {
                                $addressesById[$addr['id']] = $addr;
                            }
                            
                            foreach ($rows as &$row) {
                                $row['addresses'] = isset($addressesById[$row['address_id']]) ? $addressesById[$row['address_id']] : null;
                                $row['address'] = isset($addressesById[$row['address_id']]) ? $addressesById[$row['address_id']] : null;
                            }
                            unset($row);
                        }
                    }
                }
            }
        }
        
        echo json_encode($rows);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'SELECT execution failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── Read JSON Input Body ──
$input = json_decode(file_get_contents('php://input'), true);

// Strip fields that don't exist in the target table (e.g. updated_at on tables without it)
if (!empty($input) && in_array($method, ['POST', 'PATCH'])) {
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $tableCols = array_column($colStmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        if (!empty($tableCols)) {
            $colSet = array_flip($tableCols);
            if (!isset($input[0])) {
                $input = array_intersect_key($input, $colSet);
            } else {
                $input = array_map(function($row) use ($colSet) {
                    return array_intersect_key($row, $colSet);
                }, $input);
            }
        }
    } catch (PDOException $e) { /* proceed with original input */ }
}

// Force or default user_id for insertions (always force own ID for wishlists and carts; force own ID for non-admins on addresses and orders)
if ($method === 'POST' && in_array($table, ['addresses', 'orders', 'carts', 'wishlists'])) {
    $forceOwnId = !$isAdmin || in_array($table, ['wishlists', 'carts']);
    if (isset($input[0])) {
        foreach ($input as &$row) {
            if ($forceOwnId || !isset($row['user_id'])) {
                $row['user_id'] = $currentUserId;
            }
        }
        unset($row);
    } else {
        if ($forceOwnId || !isset($input['user_id'])) {
            $input['user_id'] = $currentUserId;
        }
    }
}

// ── POST Method (INSERT) ──
if ($method === 'POST') {
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Insert data is empty']);
        exit;
    }
    
    // Normalize to multi-row insert array
    $isMulti = isset($input[0]) && is_array($input[0]);
    $rowsToInsert = $isMulti ? $input : [$input];
    
    try {
        $insertedRows = [];
        foreach ($rowsToInsert as $rowData) {
            // Generate UUID if 'id' is required and not present
            if (!isset($rowData['id']) && in_array($table, ['products', 'categories', 'product_images', 'product_attributes', 'addresses', 'orders', 'order_items', 'banners', 'users', 'carts', 'cart_items', 'countries', 'states', 'cities', 'couriers', 'payment_gateways', 'delivery_config', 'delivery_tariffs', 'product_reviews', 'home_reviews', 'wishlists'])) {
                // simple UUIDv4 generator
                $data = random_bytes(16);
                $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                $rowData['id'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            }
            
            $cols = array_keys($rowData);
            $colNames = implode(', ', array_map(function($c) { return "`$c`"; }, $cols));
            $placeholders = implode(', ', array_map(function($c) { return ":ins_$c"; }, $cols));
            
            // Clear default address if new address is set to default
            if ($table === 'addresses') {
                $isDefault = isset($rowData['is_default']) && ($rowData['is_default'] == 1 || $rowData['is_default'] === true || $rowData['is_default'] === 'true' || $rowData['is_default'] === '1');
                if ($isDefault) {
                    $addrUserId = $rowData['user_id'] ?? $currentUserId;
                    if ($addrUserId) {
                        $stmtClear = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                        $stmtClear->execute([$addrUserId]);
                    }
                }
            }

            $stmt = $pdo->prepare("INSERT INTO `$table` ($colNames) VALUES ($placeholders)");
            
            $execParams = [];
            foreach ($rowData as $k => $v) {
                // If it is array/object, stringify it (e.g. credentials or prices JSON)
                if (is_array($v) || is_object($v)) {
                    $execParams["ins_$k"] = json_encode($v);
                } else {
                    $execParams["ins_$k"] = $v;
                }
            }
            
            $stmt->execute($execParams);
            $insertedRows[] = $rowData;
        }
        
        // Recalculate average rating if inserting product reviews
        if ($table === 'product_reviews') {
            $productIdsToUpdate = array_column($insertedRows, 'product_id');
            foreach (array_unique(array_filter($productIdsToUpdate)) as $pId) {
                $stmtAvg = $pdo->prepare("UPDATE products SET rating = COALESCE((SELECT ROUND(AVG(rating), 1) FROM product_reviews WHERE product_id = ?), 0.0) WHERE id = ?");
                $stmtAvg->execute([$pId, $pId]);
            }
        }
        
        echo json_encode($isMulti ? $insertedRows : $insertedRows[0]);
    } catch (PDOException $e) {
        $logData = date('Y-m-d H:i:s') . " - Table: $table - Error: " . $e->getMessage() . " - CurrentUserId: " . json_encode($currentUserId) . " - Input: " . json_encode($input) . " - RowData: " . (isset($rowData) ? json_encode($rowData) : 'null') . "\n";
        file_put_contents(__DIR__ . '/db_error.log', $logData, FILE_APPEND);
        http_response_code(500);
        echo json_encode(['error' => 'INSERT execution failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── PATCH Method (UPDATE) ──
if ($method === 'PATCH') {
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Update data is empty']);
        exit;
    }
    if (empty($whereClauses)) {
        http_response_code(400);
        echo json_encode(['error' => 'UPDATE requires a WHERE filter condition']);
        exit;
    }
    
    try {
        $setClauses = [];
        $updateParams = [];
        foreach ($input as $k => $v) {
            $setClauses[] = "`$k` = :upd_$k";
            if (is_array($v) || is_object($v)) {
                $updateParams["upd_$k"] = json_encode($v);
            } else {
                $updateParams["upd_$k"] = $v;
            }
        }
        
        // Clear default address if set to default
        if ($table === 'addresses') {
            $isDefault = isset($input['is_default']) && ($input['is_default'] == 1 || $input['is_default'] === true || $input['is_default'] === 'true' || $input['is_default'] === '1');
            if ($isDefault) {
                $addrUserId = $input['user_id'] ?? $currentUserId;
                if (!$addrUserId) {
                    $chkStmt = $pdo->prepare("SELECT user_id FROM addresses" . $whereSql);
                    $chkStmt->execute($params);
                    $addrUserId = $chkStmt->fetchColumn();
                }
                if ($addrUserId) {
                    $stmtClear = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                    $stmtClear->execute([$addrUserId]);
                }
            }
        }

        $setSql = implode(', ', $setClauses);
        $queryStr = "UPDATE `$table` SET $setSql" . $whereSql;
        
        $stmt = $pdo->prepare($queryStr);
        
        // Merge parameters
        $allParams = array_merge($updateParams, $params);
        $stmt->execute($allParams);
        
        // Return updated row representation by fetching
        $fetchStmt = $pdo->prepare("SELECT * FROM `$table`" . $whereSql);
        $fetchStmt->execute($params);
        $updatedRows = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recalculate average rating if updating product reviews
        if ($table === 'product_reviews') {
            $productIdsToUpdate = array_column($updatedRows, 'product_id');
            foreach (array_unique(array_filter($productIdsToUpdate)) as $pId) {
                $stmtAvg = $pdo->prepare("UPDATE products SET rating = COALESCE((SELECT ROUND(AVG(rating), 1) FROM product_reviews WHERE product_id = ?), 0.0) WHERE id = ?");
                $stmtAvg->execute([$pId, $pId]);
            }
        }
        
        echo json_encode($updatedRows);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'UPDATE execution failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── DELETE Method ──
if ($method === 'DELETE') {
    if (empty($whereClauses)) {
        http_response_code(400);
        echo json_encode(['error' => 'DELETE requires a WHERE filter condition']);
        exit;
    }
    
    try {
        // Fetch to return representation before deleting
        $fetchStmt = $pdo->prepare("SELECT * FROM `$table`" . $whereSql);
        $fetchStmt->execute($params);
        $deletedRows = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $queryStr = "DELETE FROM `$table`" . $whereSql;
        $stmt = $pdo->prepare($queryStr);
        $stmt->execute($params);
        
        // Recalculate average rating if deleting product reviews
        if ($table === 'product_reviews') {
            $productIdsToUpdate = array_column($deletedRows, 'product_id');
            foreach (array_unique(array_filter($productIdsToUpdate)) as $pId) {
                $stmtAvg = $pdo->prepare("UPDATE products SET rating = COALESCE((SELECT ROUND(AVG(rating), 1) FROM product_reviews WHERE product_id = ?), 0.0) WHERE id = ?");
                $stmtAvg->execute([$pId, $pId]);
            }
        }
        
        echo json_encode($deletedRows);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DELETE execution failed: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid method']);
