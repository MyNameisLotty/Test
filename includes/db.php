<?php
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Local configuration
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'hvf_db'; 
} else {
    // Live configuration (InfinityFree)
    $host = "sql203.infinityfree.com";
    $user = "if0_42252139";
    $pass = "UYqLZ6QobQJhGlk";
    $dbname = "if0_42252139_hvfapp";
}

// Connect using the correctly selected environment variables
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DB ERROR: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>