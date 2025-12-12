<?php
/**
 * Add Item Class Categories Script
 * This script adds comprehensive school-appropriate item class categories
 * to the existing database without affecting existing data.
 * 
 * SECURITY: Delete this file after use!
 */

require_once __DIR__ . '/../includes/Database.php';

// Simple security check - use today's date as key (YYYY-MM-DD format)
$securityKey = date('Y-m-d');
$providedKey = $_GET['key'] ?? '';

if ($providedKey !== $securityKey) {
    die("Access denied. Invalid security key. Use: ?key=" . $securityKey);
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed!");
}

// List of categories to add
$categories = [
    ['Electronics', 'Electronic devices like phones, laptops, tablets, chargers, headphones, etc.'],
    ['Bags', 'Backpacks, purses, wallets, handbags, tote bags, etc.'],
    ['Books & Notebooks', 'Textbooks, notebooks, binders, planners, study materials'],
    ['Clothing & Accessories', 'Jackets, sweaters, hats, scarves, gloves, belts, etc.'],
    ['ID Cards & Documents', 'Student IDs, driver licenses, certificates, important papers'],
    ['Keys & Keychains', 'House keys, car keys, keychains, lanyards'],
    ['Stationery & School Supplies', 'Pens, pencils, calculators, rulers, erasers, highlighters'],
    ['Jewelry & Watches', 'Rings, necklaces, bracelets, watches, earrings'],
    ['Sports Equipment', 'Balls, rackets, gym bags, sports gear'],
    ['Umbrellas', 'Umbrellas and rain gear'],
    ['Water Bottles & Containers', 'Water bottles, lunch boxes, containers, thermos'],
    ['Eyewear', 'Glasses, sunglasses, contact lens cases'],
    ['Others', 'Items that do not fit into other categories']
];

$added = 0;
$skipped = 0;
$errors = [];

try {
    foreach ($categories as $category) {
        $className = $category[0];
        $description = $category[1];
        
        // Check if category already exists
        $stmt = $conn->prepare("SELECT ItemClassID FROM itemclass WHERE ClassName = :className LIMIT 1");
        $stmt->execute(['className' => $className]);
        
        if ($stmt->fetch()) {
            // Category exists, update description if it's NULL
            $stmt = $conn->prepare("UPDATE itemclass SET Description = :description WHERE ClassName = :className AND Description IS NULL");
            $stmt->execute(['className' => $className, 'description' => $description]);
            $skipped++;
        } else {
            // Category doesn't exist, insert it
            $stmt = $conn->prepare("INSERT INTO itemclass (ClassName, Description) VALUES (:className, :description)");
            if ($stmt->execute(['className' => $className, 'description' => $description])) {
                $added++;
            } else {
                $errors[] = "Failed to add: $className";
            }
        }
    }
    
    echo "<h2>✅ Item Classes Update Complete!</h2>";
    echo "<p><strong>Added:</strong> $added new categories</p>";
    echo "<p><strong>Skipped:</strong> $skipped existing categories (updated descriptions if needed)</p>";
    
    if (!empty($errors)) {
        echo "<p><strong>Errors:</strong></p><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
    
    // Show all current categories
    echo "<hr>";
    echo "<h3>Current Item Classes:</h3>";
    $stmt = $conn->prepare("SELECT ItemClassID, ClassName, Description FROM itemclass ORDER BY ClassName");
    $stmt->execute();
    $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Category Name</th><th>Description</th></tr>";
    foreach ($allCategories as $cat) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($cat['ItemClassID']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($cat['ClassName']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($cat['Description'] ?? 'No description') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT:</strong> Delete this file (add_item_classes.php) after use for security!</p>";
?>

