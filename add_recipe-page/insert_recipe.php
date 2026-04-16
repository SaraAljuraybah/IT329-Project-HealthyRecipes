<?php
session_start();
require_once __DIR__ . '/../db.php';

// Security
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$userID = $_SESSION['user_id'];

$name = trim($_POST['name'] ?? '');
$categoryID = (int)($_POST['categoryID'] ?? 0);
$lunchBoxType = trim($_POST['lunchBoxType'] ?? '');
$description = trim($_POST['description'] ?? '');
$videoURL = trim($_POST['videoURL'] ?? '');

$ingredientNames = $_POST['ingredientName'] ?? [];
$ingredientQuantities = $_POST['ingredientQuantity'] ?? [];
$steps = $_POST['step'] ?? [];

// Basic validation
if ($name === '' || $categoryID <= 0 || $lunchBoxType === '' || $description === '') {
    die("Please fill in all required fields.");
}

if (empty($_FILES['photo']['name'])) {
    die("Photo is required.");
}

if (!empty($_FILES['videoFile']['name']) && $videoURL !== '') {
    die("Please choose either a video file or a video URL, not both.");
}

// Validate at least one full ingredient
$hasValidIngredient = false;
for ($i = 0; $i < count($ingredientNames); $i++) {
    $ingredientName = trim($ingredientNames[$i] ?? '');
    $ingredientQuantity = trim($ingredientQuantities[$i] ?? '');

    if ($ingredientName !== '' && $ingredientQuantity !== '') {
        $hasValidIngredient = true;
        break;
    }
}

if (!$hasValidIngredient) {
    die("At least one complete ingredient is required.");
}

// Validate at least one step
$hasValidStep = false;
foreach ($steps as $stepText) {
    if (trim($stepText) !== '') {
        $hasValidStep = true;
        break;
    }
}

if (!$hasValidStep) {
    die("At least one instruction step is required.");
}

// Use folders that match your display pages
$imageFolder = "../media/recipes/";
$videoFolder = "../media/recipes/";

if (!is_dir($imageFolder)) {
    mkdir($imageFolder, 0777, true);
}

if (!is_dir($videoFolder)) {
    mkdir($videoFolder, 0777, true);
}

// Upload photo
$photoFileName = time() . "_" . basename($_FILES['photo']['name']);
$photoTarget = $imageFolder . $photoFileName;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoTarget)) {
    die("Failed to upload photo.");
}

// Upload video if exists
$videoFilePath = null;

if (!empty($_FILES['videoFile']['name'])) {
    $videoFileName = time() . "_" . basename($_FILES['videoFile']['name']);
    $videoTarget = $videoFolder . $videoFileName;

    if (!move_uploaded_file($_FILES['videoFile']['tmp_name'], $videoTarget)) {
        die("Failed to upload video.");
    }

    $videoFilePath = $videoFileName;
}

$conn->begin_transaction();

try {
    $stmtRecipe = $conn->prepare("
        INSERT INTO recipe (userID, categoryID, lunchBoxType, name, description, photoFileName, videoFilePath, videoURL)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtRecipe->bind_param(
        "iissssss",
        $userID,
        $categoryID,
        $lunchBoxType,
        $name,
        $description,
        $photoFileName,
        $videoFilePath,
        $videoURL
    );

    $stmtRecipe->execute();
    $recipeID = $conn->insert_id;

    $stmtIngredient = $conn->prepare("
        INSERT INTO ingredients (recipeID, ingredientName, ingredientQuantity)
        VALUES (?, ?, ?)
    ");

    for ($i = 0; $i < count($ingredientNames); $i++) {
        $ingredientName = trim($ingredientNames[$i] ?? '');
        $ingredientQuantity = trim($ingredientQuantities[$i] ?? '');

        if ($ingredientName !== '' && $ingredientQuantity !== '') {
            $stmtIngredient->bind_param("iss", $recipeID, $ingredientName, $ingredientQuantity);
            $stmtIngredient->execute();
        }
    }

    $stmtInstruction = $conn->prepare("
        INSERT INTO instructions (recipeID, step, stepOrder)
        VALUES (?, ?, ?)
    ");

    $stepOrder = 1;
    foreach ($steps as $stepText) {
        $stepText = trim($stepText);

        if ($stepText !== '') {
            $stmtInstruction->bind_param("isi", $recipeID, $stepText, $stepOrder);
            $stmtInstruction->execute();
            $stepOrder++;
        }
    }

    $conn->commit();
    header("Location: ../my_recipes-page/my-recipes.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();

    // optional cleanup if insert fails
    if (file_exists($photoTarget)) {
        @unlink($photoTarget);
    }

    if (!empty($videoFilePath) && file_exists($videoFolder . $videoFilePath)) {
        @unlink($videoFolder . $videoFilePath);
    }

    die("Insert failed: " . $e->getMessage());
}
?>