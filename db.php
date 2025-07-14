<?php
$host = 'localhost';
$dbname = 'dbklnrz5sk3w1g';
$username = 'uaozeqcbxyhyg';
$password = 'f4kld3wzz1v3';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please try again later.");
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}
?>
