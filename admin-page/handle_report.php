<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../db.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login-page/login.html?error=Access denied");
    exit();
}

if (!isset($_POST['reportID']) || !isset($_POST['recipeID']) || !isset($_POST['action'])) {
    die("Invalid request.");
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
    
    $sqlOwner = "SELECT recipe.userID, user.firstName, user.lastName, user.emailAddress
                 FROM recipe
                 JOIN user ON recipe.userID = user.id
                 WHERE recipe.id = ?";
    $stmtOwner = mysqli_prepare($conn, $sqlOwner);
    mysqli_stmt_bind_param($stmtOwner, "i", $recipeID);
    mysqli_stmt_execute($stmtOwner);
    $resultOwner = mysqli_stmt_get_result($stmtOwner);

    if (!$resultOwner || mysqli_num_rows($resultOwner) == 0) {
        die("Recipe owner not found.");
    }

    $owner = mysqli_fetch_assoc($resultOwner);
    $ownerID = intval($owner['userID']);

    
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

    
    $sqlRecipes = "SELECT id, photoFileName, videoFilePath FROM recipe WHERE userID = ?";
    $stmtRecipes = mysqli_prepare($conn, $sqlRecipes);
    mysqli_stmt_bind_param($stmtRecipes, "i", $ownerID);
    mysqli_stmt_execute($stmtRecipes);
    $resultRecipes = mysqli_stmt_get_result($stmtRecipes);

    while ($recipe = mysqli_fetch_assoc($resultRecipes)) {
    $photo = $recipe['photoFileName'];
    $video = $recipe['videoFilePath'];

        
        $recipePhotoPath = "../uploads/images/" . $photo;
        if (!empty($photo) && file_exists($recipePhotoPath)) {
            @unlink($recipePhotoPath);
        }

        
        $videoPath = "../uploads/videos/" . $video;
        if (!empty($video) && file_exists($videoPath)) {
            @unlink($videoPath);
        }
    }

    $userPic = $owner['photoFileName'];
    $profilePath = "../uploads/profiles/" . $userPic;

    if (!empty($userPic) && $userPic != "default-user.png" && file_exists($profilePath)) {
        @unlink($profilePath);
    }

    if (!empty($video)) {
         foreach ($possibleVideoPaths as $path) {
             if (file_exists($path) && is_file($path)) {
                @unlink($path);
                 }
           }
        }
    

    $sqlDeleteUser = "DELETE FROM user WHERE id = ?";
    $stmtDeleteUser = mysqli_prepare($conn, $sqlDeleteUser);
    mysqli_stmt_bind_param($stmtDeleteUser, "i", $ownerID);
    mysqli_stmt_execute($stmtDeleteUser);

    $sqlDeleteReport = "DELETE FROM report WHERE id = ?";
    $stmtDeleteReport = mysqli_prepare($conn, $sqlDeleteReport);
    mysqli_stmt_bind_param($stmtDeleteReport, "i", $reportID);
    mysqli_stmt_execute($stmtDeleteReport);

    header("Location: admin.php");
    exit();
}

die("Invalid action.");
?>