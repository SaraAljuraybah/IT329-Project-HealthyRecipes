<?php
session_start();
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['recipe_id'])) {
    echo json_encode(["success" => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$recipeID = (int) $_POST['recipe_id'];

$checkStmt = $conn->prepare("SELECT photoFileName, videoFilePath FROM recipe WHERE id = ? AND userID = ?");
$checkStmt->bind_param("ii", $recipeID, $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(["success" => false]);
    exit();
}

$recipe = $checkResult->fetch_assoc();
$photoFileName = $recipe['photoFileName'] ?? '';
$videoFilePath = $recipe['videoFilePath'] ?? '';

$conn->begin_transaction();

try {
    $tables = ["ingredients", "instructions", "likes", "favourites", "comment", "report"];

    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE recipeID = ?");
        $stmt->bind_param("i", $recipeID);
        $stmt->execute();
    }

    $deleteStmt = $conn->prepare("DELETE FROM recipe WHERE id = ? AND userID = ?");
    $deleteStmt->bind_param("ii", $recipeID, $user_id);
    $deleteStmt->execute();

    if ($deleteStmt->affected_rows === 0) {
        throw new Exception("Delete failed");
    }

    $conn->commit();

    if (!empty($photoFileName)) {
        $photoPath = __DIR__ . '/../uploads/images/' . $photoFileName;
        if (file_exists($photoPath)) {
            @unlink($photoPath);
        }
    }

    if (!empty($videoFilePath)) {
        $videoPath = __DIR__ . '/../uploads/videos/' . $videoFilePath;
        if (file_exists($videoPath)) {
            @unlink($videoPath);
        }
    }

    echo json_encode(["success" => true]);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false]);
    exit();
}
?>