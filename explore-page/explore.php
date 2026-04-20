<?php
session_start();
include '../db.php';

// #5 - Security: must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

// Get categories from database
$sqlCategories = "SELECT * FROM recipecategory";
$categoriesResult = $conn->query($sqlCategories);

// Get recipes (all or filtered)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['category'])) {
    $categoryID = $_POST['category'];
    if ($categoryID == 'all') {
        $sqlRecipes = "SELECT recipe.*, user.firstName, user.lastName,
                       user.photoFileName AS userPhoto,
                       recipecategory.categoryName,
                       COUNT(likes.recipeID) AS likeCount
                       FROM recipe
                       JOIN user ON recipe.userID = user.id
                       JOIN recipecategory ON recipe.categoryID = recipecategory.id
                       LEFT JOIN likes ON likes.recipeID = recipe.id
                       GROUP BY recipe.id";
    } else {
        $sqlRecipes = "SELECT recipe.*, user.firstName, user.lastName,
                       user.photoFileName AS userPhoto,
                       recipecategory.categoryName,
                       COUNT(likes.recipeID) AS likeCount
                       FROM recipe
                       JOIN user ON recipe.userID = user.id
                       JOIN recipecategory ON recipe.categoryID = recipecategory.id
                       LEFT JOIN likes ON likes.recipeID = recipe.id
                       WHERE recipe.categoryID = $categoryID
                       GROUP BY recipe.id";
    }
} else {
    $sqlRecipes = "SELECT recipe.*, user.firstName, user.lastName,
                   user.photoFileName AS userPhoto,
                   recipecategory.categoryName,
                   COUNT(likes.recipeID) AS likeCount
                   FROM recipe
                   JOIN user ON recipe.userID = user.id
                   JOIN recipecategory ON recipe.categoryID = recipecategory.id
                   LEFT JOIN likes ON likes.recipeID = recipe.id
                   GROUP BY recipe.id";
}
$recipesResult = $conn->query($sqlRecipes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Explore Deliciousness!</title>
  <link rel="stylesheet" href="explore-style.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="#">
        <img class="brand-logo" src="../uploads/images/logo.png" alt="Lunchy logo">
        <span class="brand-text">
          <span class="brand-name">Lunchy</span>
          <span class="brand-tagline">Pack smart. Eat better.</span>
        </span>
      </a>

      <nav class="nav">
        <a class="nav-link" href="explore.php">Explore</a>
        <a class="nav-link" href="../my_recipes-page/my-recipes.php">My Recipes</a>
        <a class="nav-link" href="../about-us-page/about-us.html">About Us</a>
      </nav>

      <div class="actions">
        <a class="btn btn-primary" href="../user-page/user.php">My Profile</a>
        <a class="btn btn-ghost" href="../logout.php">Log Out</a>
      </div>
    </div>
  </header>

  <section class="panel">
    <div class="section-head">
      <div>
        <h2>Explore Lunchboxes</h2><br>
      </div>

      <!-- Filter form -->
      <form method="POST" action="explore.php" class="filter-ui">
        <select name="category" class="select" aria-label="Filter by category">
          <option value="all">All Categories</option>
          <?php while ($cat = $categoriesResult->fetch_assoc()): ?>
            <option value="<?php echo $cat['id']; ?>"
              <?php if (isset($_POST['category']) && $_POST['category'] == $cat['id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($cat['categoryName']); ?>
            </option>
          <?php endwhile; ?>
        </select>
        <button class="btn-filter" type="submit">Filter</button>
      </form>
    </div>

    <!-- Recipe cards -->
    <?php if ($recipesResult && $recipesResult->num_rows > 0): ?>
    <div class="recipe-grid">
      <?php while ($recipe = $recipesResult->fetch_assoc()): ?>
      <article class="recipe-card">
        <a href="../view_recipe-page/view_recipe.php?id=<?php echo $recipe['id']; ?>">
          <img class="r-img"
               src="../uploads/images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>"
               alt="<?php echo htmlspecialchars($recipe['name']); ?>">
        </a>
        <div class="r-body">
          <div class="r-top">
            <span class="cat balanced"><?php echo htmlspecialchars($recipe['categoryName']); ?></span>
            <span class="likes">❤️ <?php echo $recipe['likeCount']; ?></span>
          </div>
          <h3 class="r-title">
            <a href="../view_recipe-page/view_recipe.php?id=<?php echo $recipe['id']; ?>">
              <?php echo htmlspecialchars($recipe['name']); ?>
            </a>
          </h3>
          <p class="muted r-desc">
            <?php echo htmlspecialchars(substr($recipe['description'], 0, 60)) . '...'; ?>
          </p>
          <div class="r-creator">
            <img class="mini-ava"
                 src="../uploads/images/<?php echo htmlspecialchars($recipe['userPhoto']); ?>"
                 alt="Creator">
            <span class="muted">
              <?php echo htmlspecialchars($recipe['firstName'] . ' ' . $recipe['lastName']); ?>
            </span>
          </div>
        </div>
      </article>
      <?php endwhile; ?>
    </div>
    <?php else: ?>
      <p class="muted" style="margin-top: 2rem;">No recipes found in this category.</p>
    <?php endif; ?>

  </section>

  <footer class="site-footer">
    <div class="container footer-inner">
      <span>&copy; 2026 Lunchy. All rights reserved.</span>
    </div>
  </footer>

</body>
</html>