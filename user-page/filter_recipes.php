<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

if ($_SESSION['user_type'] != 'user') {
    echo json_encode([]);
    exit();
}

$categoryID = $_GET['category'] ?? 'all';

if ($categoryID === 'all') {
    $sql = "SELECT recipe.id, recipe.name, recipe.description, recipe.photoFileName,
                   user.firstName, user.lastName,
                   user.photoFileName AS userPhoto,
                   recipecategory.categoryName,
                   COUNT(likes.recipeID) AS likeCount
            FROM recipe
            JOIN user ON recipe.userID = user.id
            JOIN recipecategory ON recipe.categoryID = recipecategory.id
            LEFT JOIN likes ON likes.recipeID = recipe.id
            GROUP BY recipe.id";

    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT recipe.id, recipe.name, recipe.description, recipe.photoFileName,
                   user.firstName, user.lastName,
                   user.photoFileName AS userPhoto,
                   recipecategory.categoryName,
                   COUNT(likes.recipeID) AS likeCount
            FROM recipe
            JOIN user ON recipe.userID = user.id
            JOIN recipecategory ON recipe.categoryID = recipecategory.id
            LEFT JOIN likes ON likes.recipeID = recipe.id
            WHERE recipe.categoryID = ?
            GROUP BY recipe.id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryID);
}

$stmt->execute();
$result = $stmt->get_result();

$recipes = [];

while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
}

echo json_encode($recipes);
?>
