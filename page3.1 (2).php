<?php
// Error reporting for development - adjust for production
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 for production
ini_set('log_errors', 1);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration - UPDATED FOR SHARED DATABASE
$host = '172.17.35.111';        // Your IP address
$user = 'fitai_remote';         // The remote user we created
$password = 'fitai123';         // The password we set
$database = 'fitai';
$port = 3306;                   // MySQL port

// Initialize connection variable
$conn = null;

// Create connection with error handling
try {
    $conn = new mysqli($host, $user, $password, $database, $port);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset to support all characters
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }
    
    // Log successful connection
    error_log("✅ Connected to shared database at " . $host);
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    
    // For development, show the actual error
    // For production, use generic message
    if (ini_get('display_errors')) {
        die("Database Error: " . $e->getMessage());
    } else {
        die("System temporarily unavailable. Please try again later.");
    }
}
?>