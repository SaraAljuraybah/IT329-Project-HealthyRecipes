<?php
session_start();
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

$userID = $_SESSION['user_id'];

// Get form data
$recipeID = isset($_POST['recipeID']) ? (int) $_POST['recipeID'] : 0;
$name = trim($_POST['name'] ?? '');
$categoryID = isset($_POST['categoryID']) ? (int) $_POST['categoryID'] : 0;
$description = trim($_POST['description'] ?? '');
$videoURL = trim($_POST['videoURL'] ?? '');
$oldPhoto = $_POST['oldPhoto'] ?? '';
$oldVideoFile = $_POST['oldVideoFile'] ?? '';

$ingredientNames = $_POST['ing_name'] ?? [];
$ingredientQtys = $_POST['ing_qty'] ?? [];
$steps = $_POST['step'] ?? [];

// Basic validation
if ($recipeID <= 0 || $name === '' || $categoryID <= 0 || $description === '') {
    die("Missing required fields.");
}

// Check recipe belongs to logged-in user
$checkSql = "SELECT * FROM recipe WHERE id = ? AND userID = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("ii", $recipeID, $userID);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    die("Recipe not found or you do not have permission to edit it.");
}

$recipe = $checkResult->fetch_assoc();

// Handle photo upload
$newPhotoName = $oldPhoto;
$imageFolder = "../uploads/images/";

if (!is_dir($imageFolder)) {
    mkdir($imageFolder, 0777, true);
}

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0 && $_FILES['photo']['name'] !== '') {
    $photoTmp = $_FILES['photo']['tmp_name'];
    $photoName = time() . "_" . basename($_FILES['photo']['name']);
    $photoTarget = $imageFolder . $photoName;

    if (move_uploaded_file($photoTmp, $photoTarget)) {
        $newPhotoName = $photoName;

        // delete old photo if it exists
        if (!empty($oldPhoto) && file_exists($imageFolder . $oldPhoto)) {
            @unlink($imageFolder . $oldPhoto);
        }
    }
}

// Handle video upload
$newVideoFile = $oldVideoFile;
$videoFolder = "../uploads/videos/";

if (!is_dir($videoFolder)) {
    mkdir($videoFolder, 0777, true);
}

if (isset($_FILES['videoFile']) && $_FILES['videoFile']['error'] === 0 && $_FILES['videoFile']['name'] !== '') {
    $videoTmp = $_FILES['videoFile']['tmp_name'];
    $videoName = time() . "_" . basename($_FILES['videoFile']['name']);
    $videoTarget = $videoFolder . $videoName;

    if (move_uploaded_file($videoTmp, $videoTarget)) {
        $newVideoFile = $videoName;

        // delete old video file
        if (!empty($oldVideoFile) && file_exists($videoFolder . $oldVideoFile)) {
            @unlink($videoFolder . $oldVideoFile);
        }

        // if new video file uploaded, clear URL
        $videoURL = '';
    }
}


// Update recipe table
$updateSql = "UPDATE recipe 
              SET categoryID = ?, name = ?, description = ?, photoFileName = ?, videoFilePath = ?, videoURL = ?
              WHERE id = ? AND userID = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param(
    "isssssii",
    $categoryID,
    $name,
    $description,
    $newPhotoName,
    $newVideoFile,
    $videoURL,
    $recipeID,
    $userID
);

if (!$updateStmt->execute()) {
    die("Failed to update recipe: " . $conn->error);
}

// Replace ingredients
$deleteIngSql = "DELETE FROM ingredients WHERE recipeID = ?";
$deleteIngStmt = $conn->prepare($deleteIngSql);
$deleteIngStmt->bind_param("i", $recipeID);
$deleteIngStmt->execute();

$insertIngSql = "INSERT INTO ingredients (recipeID, ingredientName, ingredientQuantity) VALUES (?, ?, ?)";
$insertIngStmt = $conn->prepare($insertIngSql);

for ($i = 0; $i < count($ingredientNames); $i++) {
    $ingName = trim($ingredientNames[$i]);
    $ingQty = trim($ingredientQtys[$i] ?? '');

    if ($ingName !== '' && $ingQty !== '') {
        $insertIngStmt->bind_param("iss", $recipeID, $ingName, $ingQty);
        $insertIngStmt->execute();
    }
}

// Replace instructions
$deleteInsSql = "DELETE FROM instructions WHERE recipeID = ?";
$deleteInsStmt = $conn->prepare($deleteInsSql);
$deleteInsStmt->bind_param("i", $recipeID);
$deleteInsStmt->execute();

$insertInsSql = "INSERT INTO instructions (recipeID, stepOrder, step) VALUES (?, ?, ?)";
$insertInsStmt = $conn->prepare($insertInsSql);

$stepOrder = 1;
foreach ($steps as $stepText) {
    $stepText = trim($stepText);

    if ($stepText !== '') {
        $insertInsStmt->bind_param("iis", $recipeID, $stepOrder, $stepText);
        $insertInsStmt->execute();
        $stepOrder++;
    }
}

header("Location: ../my_recipes-page/my-recipes.php");
exit();
?>