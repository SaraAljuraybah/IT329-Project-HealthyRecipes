<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   DELETE RECIPE (same page)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe_id'])) {
    $recipeID = (int) $_POST['delete_recipe_id'];

    // Check recipe belongs to this user
    $checkStmt = $conn->prepare("SELECT photoFileName, videoFilePath FROM recipe WHERE id = ? AND userID = ?");
    $checkStmt->bind_param("ii", $recipeID, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $recipe = $checkResult->fetch_assoc();
        $photoFileName = $recipe['photoFileName'] ?? '';
        $videoFilePath = $recipe['videoFilePath'] ?? '';

        $conn->begin_transaction();

        try {
            // delete related records first
            $tables = ["ingredients", "instructions", "likes", "favourites", "comment", "report"];

            foreach ($tables as $table) {
                $stmt = $conn->prepare("DELETE FROM $table WHERE recipeID = ?");
                $stmt->bind_param("i", $recipeID);
                $stmt->execute();
            }

            // delete recipe itself
            $deleteStmt = $conn->prepare("DELETE FROM recipe WHERE id = ? AND userID = ?");
            $deleteStmt->bind_param("ii", $recipeID, $user_id);
            $deleteStmt->execute();

            if ($deleteStmt->affected_rows === 0) {
                throw new Exception("Recipe deletion failed.");
            }

            $conn->commit();

            // delete files after DB success
            if (!empty($photoFileName)) {
                $photoPath1 = __DIR__ . '/../media/recipes/' . $photoFileName;
                $photoPath2 = __DIR__ . '/../media/' . $photoFileName;

                if (file_exists($photoPath1)) {
                    @unlink($photoPath1);
                } elseif (file_exists($photoPath2)) {
                    @unlink($photoPath2);
                }
            }

            if (!empty($videoFilePath)) {
                $videoPath = __DIR__ . '/../media/recipes/' . $videoFilePath;
                if (file_exists($videoPath)) {
                    @unlink($videoPath);
                }
            }

            $_SESSION['flash_message'] = "Recipe deleted successfully.";
            $_SESSION['flash_type'] = "success";
            header("Location: my-recipes.php");
            exit();            

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: my-recipes.php?deleted=0");
            exit();
        }
    } else {
            $_SESSION['flash_message'] = "Failed to delete recipe.";
            $_SESSION['flash_type'] = "error";
            header("Location: my-recipes.php");
            exit();      
    }
}

/* =========================
   GET USER RECIPES
   ========================= */
$sql = "SELECT recipe.*, recipecategory.categoryName
        FROM recipe
        JOIN recipecategory ON recipe.categoryID = recipecategory.id
        WHERE recipe.userID = ?
        ORDER BY recipe.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Recipes - Lunchy</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="my-recipes-style.css">
</head>


<body>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="../explore-page/explore.html">
            <img class="brand-logo" src="../media/logo.png" alt="Lunchy logo">
            <span class="brand-text">
                <span class="brand-name">Lunchy</span>
                <span class="brand-tagline">Pack smart. Eat better.</span>
            </span>
        </a>
        <nav class="nav">
            <a class="nav-link" href="../explore-page/explore.html">Explore</a>
            <a class="nav-link" href="../my_recipes-page/my-recipes.php">My Recipes</a>
            <a class="nav-link" href="../about-us-page/about-us.html">About Us</a>
        </nav>
        <div class="actions">
            <a class="btn btn-primary" href="../user-page/user.php">My Profile</a>
            <a class="btn btn-ghost" href="../logout.php">Log Out</a>
        </div>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1 class="page-title">My Recipes</h1>
        <a href="../add_recipe-page/add-recipe.php" class="btn-add">Add New Recipe</a>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="empty-state">
            <h3>No recipes yet!</h3>
            <p>Start by adding your first recipe.</p>
        </div>
    <?php else: ?>
    
<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="flash-message <?php echo $_SESSION['flash_type'] === 'success' ? 'flash-success' : 'flash-error'; ?>">
        <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
    </div>
    <?php
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    ?>
<?php endif; ?>

    <div class="recipes-grid">
        <?php while ($row = $result->fetch_assoc()): ?>
        <?php
            $recipeID = $row['id'];

            $likesStmt = $conn->prepare("SELECT COUNT(*) AS totalLikes FROM likes WHERE recipeID = ?");
            $likesStmt->bind_param("i", $recipeID);
            $likesStmt->execute();
            $totalLikes = $likesStmt->get_result()->fetch_assoc()['totalLikes'];

            $ingStmt = $conn->prepare("SELECT ingredientName, ingredientQuantity FROM ingredients WHERE recipeID = ?");
            $ingStmt->bind_param("i", $recipeID);
            $ingStmt->execute();
            $ingredients = $ingStmt->get_result();

            $insStmt = $conn->prepare("SELECT step FROM instructions WHERE recipeID = ? ORDER BY stepOrder ASC");
            $insStmt->bind_param("i", $recipeID);
            $insStmt->execute();
            $instructions = $insStmt->get_result();
        ?>
        <div class="recipe-card">
            <div class="recipe-header">
                <a href="../view_recipe-page/View-recipe-page.php?id=<?php echo $recipeID; ?>">
                    <img src="../uploads/images/<?php echo htmlspecialchars($row['photoFileName']); ?>"
                         alt="<?php echo htmlspecialchars($row['name']); ?>"
                         class="recipe-thumb-large">
                </a>
                <div class="recipe-badge">❤ <?php echo $totalLikes; ?> likes</div>
            </div>

            <div class="recipe-body">
                <a href="../view_recipe-page/View-recipe-page.php?id=<?php echo $recipeID; ?>" class="recipe-title">
                    <?php echo htmlspecialchars($row['name']); ?>
                </a>

                <div class="recipe-section">
                    <div class="section-label">Ingredients</div>
                    <ul class="ingredients-list">
                        <?php while ($ing = $ingredients->fetch_assoc()): ?>
                            <li><?php echo htmlspecialchars($ing['ingredientName']); ?> - <?php echo htmlspecialchars($ing['ingredientQuantity']); ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="recipe-section">
                    <div class="section-label">Instructions</div>
                    <ol class="instructions-list">
                        <?php while ($ins = $instructions->fetch_assoc()): ?>
                            <li><?php echo htmlspecialchars($ins['step']); ?></li>
                        <?php endwhile; ?>
                    </ol>
                </div>

                <div class="video-container">
                    <?php if (!empty($row['videoFilePath'])): ?>
                        <video controls class="recipe-video">
                            <source src="../uploads/videos/<?php echo htmlspecialchars($row['videoFilePath']); ?>" type="video/mp4">
                        </video>
                    <?php elseif (!empty($row['videoURL'])): ?>
                        <a href="<?php echo htmlspecialchars($row['videoURL']); ?>" target="_blank">Watch Video</a>
                    <?php else: ?>
                        <span class="no-video">No video for recipe</span>
                    <?php endif; ?>
                </div>

                <div class="recipe-footer">
                    <div class="action-buttons">
                        <a href="../edit_recipe-page/edit_recipe.php?id=<?php echo $recipeID; ?>" class="action-link edit-link">Edit</a>
                        <form method="POST" action="my-recipes.php" style="display:inline;" class="delete-form">
                            <input type="hidden" name="delete_recipe_id" value="<?php echo $recipeID; ?>">
                            <button type="button" class="action-link delete-link open-delete-modal">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>&copy; 2026 Lunchy. All rights reserved.</span>
    </div>
</footer>
<div id="deleteModal" class="delete-modal hidden">
  <div class="delete-modal-card">
    <div class="delete-modal-icon">🗑️</div>
    <h3>Delete Recipe?</h3>
    <p>Are you sure you want to delete this recipe? This action cannot be undone.</p>

    <div class="delete-modal-actions">
      <button type="button" id="cancelDeleteBtn" class="btn btn-ghost modal-btn">Cancel</button>
      <button type="button" id="confirmDeleteBtn" class="btn btn-danger modal-btn">Yes, Delete</button>
    </div>
  </div>
</div>
<script>
  const deleteModal = document.getElementById("deleteModal");
  const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  const openDeleteButtons = document.querySelectorAll(".open-delete-modal");

  let selectedDeleteForm = null;

  openDeleteButtons.forEach(button => {
    button.addEventListener("click", function () {
      selectedDeleteForm = this.closest(".delete-form");
      deleteModal.classList.remove("hidden");
      document.body.style.overflow = "hidden";
    });
  });

  cancelDeleteBtn.addEventListener("click", function () {
    deleteModal.classList.add("hidden");
    selectedDeleteForm = null;
    document.body.style.overflow = "";
  });

  confirmDeleteBtn.addEventListener("click", function () {
    if (selectedDeleteForm) {
      selectedDeleteForm.submit();
    }
  });

  deleteModal.addEventListener("click", function (e) {
    if (e.target === deleteModal) {
      deleteModal.classList.add("hidden");
      selectedDeleteForm = null;
      document.body.style.overflow = "";
    }
  });
</script>
</body>
</html>