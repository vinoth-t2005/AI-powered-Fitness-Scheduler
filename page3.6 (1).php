<?php
require_once 'page3.1.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: page3.2.php');
exit();
?>