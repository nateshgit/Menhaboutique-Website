<?php
require_once __DIR__ . '/../config/db.php';
$db = getDBConnection();
$stmt = $db->query("SELECT DISTINCT attribute_type FROM product_attributes");
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Unique Attribute Types:\n";
print_r($types);

$stmt2 = $db->query("SELECT attribute_type, attribute_value, COUNT(*) as count FROM product_attributes GROUP BY attribute_type, attribute_value LIMIT 20");
echo "\nSample Attribute Values:\n";
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Type: {$row['attribute_type']} | Value: {$row['attribute_value']} | Count: {$row['count']}\n";
}
