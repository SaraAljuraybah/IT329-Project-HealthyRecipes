<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}
include("../db.php");

if (!isset($_POST['recipeID'])) {
    die("recipeID is missing");
}

$recipeID = intval($_POST['recipeID']);
$userID = $_SESSION['user_id'];

$checkSql = "SELECT * FROM favourites WHERE userID = ? AND recipeID = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, "ii", $userID, $recipeID);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) == 0) {
    $sql = "INSERT INTO favourites (userID, recipeID) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $userID, $recipeID);
    mysqli_stmt_execute($stmt);
}

 header("Location: view_recipe.php?id=" . $recipeID);exit();
?>