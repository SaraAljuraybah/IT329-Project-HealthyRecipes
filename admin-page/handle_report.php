<?php
// Buffer ALL output so any PHP warnings/notices don't corrupt the JSON response
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Never let PHP errors bleed into AJAX responses

session_start();
include("../db.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    ob_end_clean();
    echo "false";
    exit();
}

if (!isset($_POST['reportID']) || !isset($_POST['recipeID']) || !isset($_POST['action'])) {
    ob_end_clean();
    echo "false";
    exit();
}

$reportID = intval($_POST['reportID']);
$recipeID = intval($_POST['recipeID']);
$action = $_POST['action'];

// ── DISMISS ───────────────────────────────────────────────────────────────────
if ($action == "dismiss") {
    $sqlDeleteReport = "DELETE FROM report WHERE id = ?";
    $stmtDeleteReport = mysqli_prepare($conn, $sqlDeleteReport);
    mysqli_stmt_bind_param($stmtDeleteReport, "i", $reportID);
    $success = mysqli_stmt_execute($stmtDeleteReport);

    ob_end_clean();
    echo $success ? "true" : "false";
    exit();
}

// ── BLOCK ─────────────────────────────────────────────────────────────────────
if ($action == "block") {

    $sqlOwner = "SELECT recipe.userID, user.firstName, user.lastName, user.emailAddress, user.photoFileName
                 FROM recipe
                 JOIN user ON recipe.userID = user.id
                 WHERE recipe.id = ?";
    $stmtOwner = mysqli_prepare($conn, $sqlOwner);
    mysqli_stmt_bind_param($stmtOwner, "i", $recipeID);
    mysqli_stmt_execute($stmtOwner);
    $resultOwner = mysqli_stmt_get_result($stmtOwner);

    if (!$resultOwner || mysqli_num_rows($resultOwner) == 0) {
        ob_end_clean();
        echo "false";
        exit();
    }

    $owner = mysqli_fetch_assoc($resultOwner);
    $ownerID = intval($owner['userID']);

    $alreadyBlocked = false;

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
    } else {
        $alreadyBlocked = true;
    }

    // Delete recipe files (photos & videos)
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

    // Delete profile photo
    $userPic = $owner['photoFileName'] ?? '';
    $profilePath = "../uploads/profiles/" . $userPic;
    if (!empty($userPic) && $userPic != "default-user.png" && file_exists($profilePath)) {
        @unlink($profilePath);
    }

    // Delete the user
    $sqlDeleteUser = "DELETE FROM user WHERE id = ?";
    $stmtDeleteUser = mysqli_prepare($conn, $sqlDeleteUser);
    mysqli_stmt_bind_param($stmtDeleteUser, "i", $ownerID);
    $success = mysqli_stmt_execute($stmtDeleteUser);

    // Delete the report
    $sqlDeleteReport = "DELETE FROM report WHERE id = ?";
    $stmtDeleteReport = mysqli_prepare($conn, $sqlDeleteReport);
    mysqli_stmt_bind_param($stmtDeleteReport, "i", $reportID);
    mysqli_stmt_execute($stmtDeleteReport);

    ob_end_clean(); // Discard any warnings that may have been buffered
    if ($success) {
        header('Content-Type: application/json');
        echo json_encode([
            "success"        => true,
            "alreadyBlocked" => $alreadyBlocked,
            "firstName"      => $owner['firstName'],
            "lastName"       => $owner['lastName'],
            "emailAddress"   => $owner['emailAddress']
        ]);
    } else {
        echo "false";
    }
    exit();
}

ob_end_clean();
echo "false";
?>