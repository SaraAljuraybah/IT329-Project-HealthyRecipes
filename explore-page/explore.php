<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

$sqlCategories = "SELECT * FROM recipecategory";
$categoriesResult = $conn->query($sqlCategories);

$sqlRecipes = "SELECT recipe.*, user.firstName, user.lastName,
               user.photoFileName AS userPhoto,
               recipecategory.categoryName,
               COUNT(likes.recipeID) AS likeCount
               FROM recipe
               JOIN user ON recipe.userID = user.id
               JOIN recipecategory ON recipe.categoryID = recipecategory.id
               LEFT JOIN likes ON likes.recipeID = recipe.id
               GROUP BY recipe.id";

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

    <div class="filter-ui">
      <select id="categoryFilter" name="category" class="select" aria-label="Filter by category">
        <option value="all">All Categories</option>

        <?php while ($cat = $categoriesResult->fetch_assoc()): ?>
          <option value="<?php echo $cat['id']; ?>">
            <?php echo htmlspecialchars($cat['categoryName']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
  </div>

  <div class="recipe-grid" id="recipesContainer">
    <?php if ($recipesResult && $recipesResult->num_rows > 0): ?>
      <?php while ($recipe = $recipesResult->fetch_assoc()): ?>
        <article class="recipe-card">
          <a href="../view_recipe-page/view_recipe.php?id=<?php echo $recipe['id']; ?>">
            <img class="r-img"
                 src="../uploads/images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>"
                 alt="<?php echo htmlspecialchars($recipe['name']); ?>">
          </a>

          <div class="r-body">
            <div class="r-top">
              <span class="cat balanced">
                <?php echo htmlspecialchars($recipe['categoryName']); ?>
              </span>
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
    <?php else: ?>
      <p class="muted" style="margin-top: 2rem;">No recipes found in this category.</p>
    <?php endif; ?>
  </div>
</section>

<footer class="site-footer">
  <div class="container footer-inner">
    <span>&copy; 2026 Lunchy. All rights reserved.</span>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(document).ready(function () {
  $("#categoryFilter").change(function () {
    let categoryID = $(this).val();

    $.ajax({
      url: "filterRecipes.php",
      type: "GET",
      data: { category: categoryID },
      dataType: "json",

      success: function (recipes) {
        $("#recipesContainer").empty();

        if (recipes.length === 0) {
          $("#recipesContainer").html(
            '<p class="muted" style="margin-top: 2rem;">No recipes found in this category.</p>'
          );
          return;
        }

        recipes.forEach(function (recipe) {
          $("#recipesContainer").append(`
            <article class="recipe-card">
              <a href="../view_recipe-page/view_recipe.php?id=${recipe.id}">
                <img class="r-img"
                     src="../uploads/images/${recipe.photoFileName}"
                     alt="${recipe.name}">
              </a>

              <div class="r-body">
                <div class="r-top">
                  <span class="cat balanced">${recipe.categoryName}</span>
                  <span class="likes">❤️ ${recipe.likeCount}</span>
                </div>

                <h3 class="r-title">
                  <a href="../view_recipe-page/view_recipe.php?id=${recipe.id}">
                    ${recipe.name}
                  </a>
                </h3>

                <p class="muted r-desc">
                  ${recipe.description.substring(0, 60)}...
                </p>

                <div class="r-creator">
                  <img class="mini-ava"
                       src="../uploads/images/${recipe.userPhoto}"
                       alt="Creator">

                  <span class="muted">
                    ${recipe.firstName} ${recipe.lastName}
                  </span>
                </div>
              </div>
            </article>
          `);
        });
      },

      error: function () {
        alert("Error loading recipes.");
      }
    });
  });
});
</script>

</body>
</html>
