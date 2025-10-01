<?php
$host     = "127.0.0.1";  
$dbuser   = "root";       
$dbpass   = "";           
$dbname   = "presentech"; 
$port     = 3306;        

$conn = new mysqli($host, $dbuser, $dbpass, $dbname, $port);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
