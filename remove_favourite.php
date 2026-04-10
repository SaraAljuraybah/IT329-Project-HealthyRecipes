<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login-page/login.html");
    exit();
}

$userID = $_SESSION['user_id'];
$recipeID = $_GET['recipeID'];

$sql = "DELETE FROM favourites WHERE userID = $userID AND recipeID = $recipeID";
$conn->query($sql);

header("Location: user-page/user.php");
exit();
?>