<?php
$conn = new mysqli("localhost","root","root","lunchy_phase2",3306);

if($conn->connect_error){
    die("Connection failed");
}
?>