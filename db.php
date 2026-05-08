<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning";

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Sorry, we are experiencing technical difficulties. Please try again later.");
}
?>