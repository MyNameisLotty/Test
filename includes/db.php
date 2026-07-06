<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "sql203.infinityfree.com";
$user = "if0_42252139";
$pass = "UYqLZ6QobQJhGlk";
$dbname = "if0_42252139_hvfapp";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DB ERROR: " . $conn->connect_error);
}
?>