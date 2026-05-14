<?php
session_start();
include("../db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['recipeID'])) {
    echo json_encode(["success" => false]);
    exit();
}

$userID   = $_SESSION['user_id'];
$recipeID = intval($_POST['recipeID']);

// Check not already reported
$checkSql  = "SELECT * FROM report WHERE userID = ? AND recipeID = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, "ii", $userID, $recipeID);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    // Already reported — still return true so button disables
    echo json_encode(["success" => true]);
    exit();
}

$sql  = "INSERT INTO report (userID, recipeID) VALUES (?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $userID, $recipeID);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}

exit();
?>
