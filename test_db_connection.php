<?php
// Test database connection script
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database configuration
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'pns_dhampur';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "Testing database connection...\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $database\n";
echo "Username: $username\n";
echo "Password: " . (!empty($password) ? '***' : '(empty)') . "\n\n";

// Test connection
$dsn = "mysql:host=$host;port=$port";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    echo "✅ Connection successful!\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
    $dbExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dbExists) {
        echo "✅ Database '$database' exists\n";
        
        // Switch to database
        $pdo->exec("USE $database");
        
        // Check migrations table
        $stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
        $migrationsTable = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($migrationsTable) {
            echo "✅ Migrations table exists\n";
        } else {
            echo "⚠️  Migrations table does not exist\n";
        }
    } else {
        echo "⚠️  Database '$database' does not exist\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting steps:\n";
    echo "1. Make sure MySQL service is running in XAMPP\n";
    echo "2. Check if MySQL is listening on port $port\n";
    echo "3. Verify MySQL username/password in .env file\n";
    echo "4. Ensure database '$database' exists\n";
}

echo "\nChecking MySQL service status...\n";

// Check if MySQL port is open
echo "Checking port $port... ";
$connection = @fsockopen($host, $port, $errno, $errstr, 2);
if (is_resource($connection)) {
    echo "✅ Port $port is open\n";
    fclose($connection);
} else {
    echo "❌ Port $port is closed or unreachable\n";
    echo "Error: $errstr ($errno)\n";
}
?>