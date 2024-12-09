<?php
// Remove Composer autoloader requirement
// require_once 'vendor/autoload.php';

// Simple .env file reader function
function loadEnv($path = '.env') {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
                $value = substr($value, 1, -1);
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
    return true;
}

// Load environment variables
loadEnv();

// Update database configuration
$db_config = [
    'host' => getenv('DB_HOST') ?? 'localhost',
    'dbname' => getenv('DB_NAME') ?? 'hospital_management',
    'charset' => getenv('DB_CHARSET') ?? 'utf8mb4',
    'username' => getenv('DB_USER') ?? 'root',
    'password' => getenv('DB_PASSWORD') ?? ''
];

// Add security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");

try {
    // Create PDO instance with security options
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password'],
        [
            // Error handling
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            
            // Return results as associative arrays
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            
            // Disable emulation mode for real prepared statements
            PDO::ATTR_EMULATE_PREPARES => false,
            
            // Set character set
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$db_config['charset']} COLLATE utf8mb4_unicode_ci",
            
            // Additional security options
            PDO::ATTR_PERSISTENT => false, // Disable persistent connections
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL
        ]
    );

    // Test the connection
    $pdo->query('SELECT 1');

    // Create a function for secure parameter binding
    function bindParams($stmt, $params) {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $type = PDO::PARAM_STR; // Default type
                
                if (is_int($value)) {
                    $type = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                    $type = PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $type = PDO::PARAM_NULL;
                }
                
                $stmt->bindValue(
                    is_numeric($key) ? $key + 1 : $key,
                    $value,
                    $type
                );
            }
        }
        return $stmt;
    }

    // Update the cleanInput function with more robust sanitization
    function cleanInput($data, $text_only = false) {
        if (is_array($data)) {
            return array_map('cleanInput', $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove null bytes
        $data = str_replace(chr(0), '', $data);
        
        // Remove other potentially dangerous characters
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
        
        // If text_only is true, remove everything except letters and spaces
        if ($text_only) {
            $data = preg_replace("/[^a-zA-Z\s]/", "", $data);
        }
        
        return $data;
    }

    // Add password hashing function with modern algorithm
    function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    // Add secure password verification
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    // Add rate limiting function
    function checkRateLimit($ip, $action, $limit = 5, $timeframe = 300) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        
        $key = "ratelimit:{$ip}:{$action}";
        $current = $redis->incr($key);
        
        if ($current === 1) {
            $redis->expire($key, $timeframe);
        }
        
        return $current <= $limit;
    }

    // Update the secureQuery function with additional protections
    function secureQuery($pdo, $query, $params = []) {
        try {
            // Check for common SQL injection patterns
            if (containsSQLInjection($query)) {
                throw new Exception("Potential SQL injection detected");
            }
            
            $stmt = $pdo->prepare($query);
            bindParams($stmt, $params);
            
            // Add query timeout
            $stmt->setAttribute(PDO::ATTR_TIMEOUT, 5); // 5 second timeout
            
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database error occurred", 500);
        }
    }

    // Example usage:
    /*
    // Instead of direct queries, use:
    $result = secureQuery($pdo, 
        "SELECT * FROM users WHERE id = ? AND status = ?", 
        [$user_id, $status]
    );

    // Or with named parameters:
    $result = secureQuery($pdo,
        "SELECT * FROM users WHERE id = :id AND status = :status",
        [':id' => $user_id, ':status' => $status]
    );
    */

} catch (PDOException $e) {
    // Log the error securely
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display generic error message
    die("A database error occurred. Please try again later.");
}

// Function to validate integer input
function validateInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to check for common SQL injection patterns
function containsSQLInjection($string) {
    $sql_patterns = [
        "/(\%27)|(\')|(\-\-)|(\%23)|(#)/i",
        "/(\%3D)|(=)/i",
        "/(\%3B)|(;)/i",
        "/(\%2D)/i",
        "/UNION/i",
        "/SELECT/i",
        "/DROP/i",
        "/DELETE/i",
        "/UPDATE/i",
        "/INSERT/i",
        "/ALTER/i",
        "/CREATE/i",
        "/TRUNCATE/i"
    ];
    
    foreach ($sql_patterns as $pattern) {
        if (preg_match($pattern, $string)) {
            error_log("Potential SQL injection attempt detected: " . $string);
            return true;
        }
    }
    return false;
}

// Example of how to use these security functions:
/*
// Clean and validate input
$user_input = cleanInput($_POST['user_input']);

// Check for SQL injection attempts
if (containsSQLInjection($user_input)) {
    die("Invalid input detected");
}

// Validate specific input types
if (!validateInt($_POST['id'])) {
    die("Invalid ID format");
}

if (!validateEmail($_POST['email'])) {
    die("Invalid email format");
}
*/

// Update config.php with additional security settings
define('ADMIN_CREATION_KEY', 'HMSADMIN'); // No spaces, exactly as shown
define('MIN_USERNAME_LENGTH', 4);
define('MIN_PASSWORD_LENGTH', 8);
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_PASSWORD', 'admin123');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hospital_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('SITE_NAME', 'Hospital Management System');
define('SITE_URL', 'http://localhost/hms');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SECURE_SESSION', true);

// Add new function to validate text-only input
function validateTextOnly($input) {
    // Remove any whitespace
    $input = trim($input);
    
    // Check if the input contains only letters and spaces
    if (!preg_match("/^[a-zA-Z\s]+$/", $input)) {
        return false;
    }
    
    // Check minimum length (adjust as needed)
    if (strlen($input) < 2) {
        return false;
    }
    
    return true;
}

// Example usage in form validation:
function validateName($name) {
    $cleaned_name = cleanInput($name, true);
    if (!validateTextOnly($cleaned_name)) {
        throw new Exception("Name can only contain letters and spaces");
    }
    return $cleaned_name;
}
?>