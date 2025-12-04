
<?php
// db_config.php
ini_set('display_errors', '0');        // Turn off displaying errors
ini_set('display_startup_errors', '0'); // Turn off startup errors
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 

$servername = "localhost";
$username = "monopoly_user";
$password = "RahiqBaddie";
$dbname = "monopoly_db";

$con = mysqli_connect($servername, $username, $password, $dbname);
?>