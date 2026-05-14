<?php
session_start();
include 'db.php';

header("Content-Type: text/plain");

if (!isset($_SESSION['user_id'])) {
    echo "false";
    exit();
}

if (!isset($_POST['recipeID'])) {
    echo "false";
    exit();
}

$userID = $_SESSION['user_id'];
$recipeID = $_POST['recipeID'];

$sql = "DELETE FROM favourites 
        WHERE userID = $userID 
        AND recipeID = $recipeID";

if ($conn->query($sql) && $conn->affected_rows > 0) {
    echo "true";
} else {
    echo "false";
}
?>