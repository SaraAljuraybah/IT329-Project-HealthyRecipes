<?php
$conn = new mysqli("localhost","root","","lunchy_phase2",3306);

if($conn->connect_error){
    die("Connection failed");
}
?>
