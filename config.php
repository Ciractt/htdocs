<?php
// config.php - Database and site configuration
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'riftbound');
define('DB_PASS', '![@ef[hseux,Jv4Wxf');
define('DB_NAME', 'riftbound');

// Riot API configuration
define('RIOT_API_KEY', 'YOUR_RIOT_API_KEY_HERE');
define('RIOT_API_REGION', 'americas'); // americas, europe, asia, sea

// Site configuration
define('SITE_URL', 'https://riftvault.gg');
define('SITE_NAME', 'RiftVault.gg');

// Create database connection
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, username, email, riot_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
