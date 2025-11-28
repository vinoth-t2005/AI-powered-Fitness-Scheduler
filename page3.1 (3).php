<?php
// Error reporting for development - adjust for production
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 for production
ini_set('log_errors', 1);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'fitai';

// Initialize connection variable
$conn = null;

// Create connection with error handling
try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset to support all characters
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }
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