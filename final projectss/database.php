<?php

$servername = 'localhost';
$username= 'root';
$password= '';
$dbname= 'mytears';

$conn = mysqli_connect($servername, $username, $password, $dbname);
if ($conn) {
    //echo "Connected to database";
} else {
    die("Connection failed: " . mysqli_connect_error());
}
?>