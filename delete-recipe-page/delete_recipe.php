<?php
session_start();
require_once __DIR__ . '/../db.php';

// Security
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid recipe ID.");
}

$userID = $_SESSION['user_id'];
$recipeID = (int) $_GET['id'];

// Check recipe belongs to logged-in user
$checkSql = "SELECT photoFileName, videoFilePath FROM recipe WHERE id = ? AND userID = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("ii", $recipeID, $userID);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    die("Recipe not found or you do not have permission to delete it.");
}

$recipe = $checkResult->fetch_assoc();
$photoFileName = $recipe['photoFileName'] ?? '';
$videoFilePath = $recipe['videoFilePath'] ?? '';

$conn->begin_transaction();

try {
    // Delete related records first
    $tables = [
        "ingredients",
        "instructions",
        "likes",
        "favourites",
        "comment",
        "report"
    ];

    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE recipeID = ?");
        $stmt->bind_param("i", $recipeID);
        $stmt->execute();
    }

    // Delete recipe
    $deleteRecipeStmt = $conn->prepare("DELETE FROM recipe WHERE id = ? AND userID = ?");
    $deleteRecipeStmt->bind_param("ii", $recipeID, $userID);
    $deleteRecipeStmt->execute();

    if ($deleteRecipeStmt->affected_rows === 0) {
        throw new Exception("Recipe deletion failed.");
    }

    $conn->commit();

    // Delete files after DB success
    if (!empty($photoFileName)) {
        $photoPath = __DIR__ . "/../media/recipes/" . $photoFileName;
        if (file_exists($photoPath)) {
            @unlink($photoPath);
        }
    }

    if (!empty($videoFilePath)) {
        $videoPath = __DIR__ . "/../media/recipes/" . $videoFilePath;
        if (file_exists($videoPath)) {
            @unlink($videoPath);
        }
    }

    header("Location: ../my_recipes-page/my-recipes.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die("Delete failed: " . $e->getMessage());
}
?>