<?php
$host     = "sql309.infinityfree.com"; 
$username = "if0_41999179";              
$password = "ApdARMs3deRsIH";     
$database = "if0_41999179_dbkasir";     

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>