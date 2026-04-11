<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}
include("../db.php");

if (!isset($_POST['recipeID']) || !isset($_POST['comment'])) {
    die("Invalid request");
}

$recipeID = intval($_POST['recipeID']);
$userID = $_SESSION['user_id'];
$commentText = trim($_POST['comment']);

if ($commentText == "") {
    header("Location: view_recipe.php?id=" . $recipeID);
    exit();
}

$sql = "INSERT INTO comment (recipeID, userID, comment) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iis", $recipeID, $userID, $commentText);
mysqli_stmt_execute($stmt);

header("Location: view_recipe.php?id=" . $recipeID);
exit();
?>