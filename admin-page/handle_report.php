<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../db.php");

/* check login */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: ../login-page/login.html?error=Please login first");
    exit();
}

/* check admin */
if ($_SESSION['user_type'] != 'admin') {
    header("Location: ../login-page/login.html?error=Access denied");
    exit();
}

if (!isset($_POST['reportID']) || !isset($_POST['recipeID']) || !isset($_POST['action'])) {
    echo "Invalid request.";
    exit();
}

$reportID = intval($_POST['reportID']);
$recipeID = intval($_POST['recipeID']);
$action = $_POST['action'];

if ($action == "dismiss") {
    $sqlDeleteReport = "DELETE FROM report WHERE id = ?";
    $stmtDeleteReport = mysqli_prepare($conn, $sqlDeleteReport);
    mysqli_stmt_bind_param($stmtDeleteReport, "i", $reportID);
    mysqli_stmt_execute($stmtDeleteReport);

    header("Location: admin.php");
    exit();
}

if ($action == "block") {
    /* get recipe owner */
    $sqlRecipe = "SELECT recipe.userID, user.firstName, user.lastName, user.emailAddress
                  FROM recipe
                  JOIN user ON recipe.userID = user.id
                  WHERE recipe.id = ?";
    $stmtRecipe = mysqli_prepare($conn, $sqlRecipe);
    mysqli_stmt_bind_param($stmtRecipe, "i", $recipeID);
    mysqli_stmt_execute($stmtRecipe);
    $resultRecipe = mysqli_stmt_get_result($stmtRecipe);

    if (mysqli_num_rows($resultRecipe) == 0) {
        echo "Recipe or user not found.";
        exit();
    }

    $owner = mysqli_fetch_assoc($resultRecipe);
    $ownerID = $owner['userID'];

    /* add to blockeduser first */
    $sqlCheckBlocked = "SELECT * FROM blockeduser WHERE emailAddress = ?";
    $stmtCheckBlocked = mysqli_prepare($conn, $sqlCheckBlocked);
    mysqli_stmt_bind_param($stmtCheckBlocked, "s", $owner['emailAddress']);
    mysqli_stmt_execute($stmtCheckBlocked);
    $resultCheckBlocked = mysqli_stmt_get_result($stmtCheckBlocked);

    if (mysqli_num_rows($resultCheckBlocked) == 0) {
        $sqlInsertBlocked = "INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)";
        $stmtInsertBlocked = mysqli_prepare($conn, $sqlInsertBlocked);
        mysqli_stmt_bind_param($stmtInsertBlocked, "sss", $owner['firstName'], $owner['lastName'], $owner['emailAddress']);
        mysqli_stmt_execute($stmtInsertBlocked);
    }

    /* delete user -> recipes and related data will be deleted by cascade */
    $sqlDeleteUser = "DELETE FROM user WHERE id = ?";
    $stmtDeleteUser = mysqli_prepare($conn, $sqlDeleteUser);
    mysqli_stmt_bind_param($stmtDeleteUser, "i", $ownerID);
    mysqli_stmt_execute($stmtDeleteUser);

    /* just in case, delete the original report by id if still exists */
    $sqlDeleteReport = "DELETE FROM report WHERE id = ?";
    $stmtDeleteReport = mysqli_prepare($conn, $sqlDeleteReport);
    mysqli_stmt_bind_param($stmtDeleteReport, "i", $reportID);
    mysqli_stmt_execute($stmtDeleteReport);

    header("Location: admin.php");
    exit();
}

echo "Invalid action.";
?>