<?php
session_start();
include '../db.php';

// #5 - Security
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

// #6a - Must be regular user
if ($_SESSION['user_type'] != 'user') {
    header("Location: ../login-page/login.html?error=unauthorized");
    exit();
}

// #6b - Get user info
$userID = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM user WHERE id = $userID");
$user = $result->fetch_assoc();

// #6c - Count recipes and total likes
$sqlRecipeCount = "SELECT COUNT(*) AS total FROM recipe WHERE userID = $userID";
$recipeCountResult = $conn->query($sqlRecipeCount);
$recipeCount = $recipeCountResult->fetch_assoc()['total'];

$sqlLikesCount = "SELECT COUNT(*) AS total FROM likes 
                  WHERE recipeID IN (SELECT id FROM recipe WHERE userID = $userID)";
$likesCountResult = $conn->query($sqlLikesCount);
$likesCount = $likesCountResult->fetch_assoc()['total'];

// #6d - Get categories from database
$sqlCategories = "SELECT * FROM recipecategory";
$categoriesResult = $conn->query($sqlCategories);

// #6e - Get recipes (all or filtered)
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

// #6f - Get favourites
$sqlFavs = "SELECT recipe.*, user.firstName, user.lastName 
            FROM favourites
            JOIN recipe ON favourites.recipeID = recipe.id
            JOIN user ON recipe.userID = user.id
            WHERE favourites.userID = $userID";
$favsResult = $conn->query($sqlFavs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Dashboard | LunchBoxLab</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="user.css">
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="../explore-page/explore.php">
        <img class="brand-logo" src="../uploads/images/logo.png" alt="Lunchy logo">
        <span class="brand-text">
          <span class="brand-name">Lunchy</span>
          <span class="brand-tagline">Pack smart. Eat better.</span>
        </span>
      </a>
      <nav class="nav">
        <a class="nav-link" href="../explore-page/explore.php">Explore</a>
        <a class="nav-link" href="../my_recipes-page/my-recipes.php">My Recipes</a>
        <a class="nav-link" href="../about-us-page/about-us.html">About Us</a>
      </nav>
      <div class="actions">
        <a class="btn btn-primary" href="user.php">My Profile</a>
        <a class="btn btn-ghost" href="../logout.php">Log Out</a>
      </div>
    </div>
  </header>

  <main class="container user-wrap">

    <!-- 1) Welcome -->
    <section class="hero-user">
      <div>
        <div class="pill">☀️ Good morning</div>
        <h1>Hi, <?php echo htmlspecialchars($user['firstName']); ?>!</h1>
        <p class="muted">Ready to pack something healthy today?</p>
        <div class="chip-row">
          <span class="chip chip-balanced">Balanced</span>
          <span class="chip chip-protein">High Protein</span>
          <span class="chip chip-lowcarb">Low Carb</span>
        </div>
      </div>
      <div class="quick-box">
        <div class="quick-title">Today's goal</div>
        <div class="quick-line">✅ 1 main + 1 snack + water</div>
        <div class="quick-line muted">Tip: add fruit for freshness 🍓</div>
      </div>
    </section>

    <!-- 2) Profile + 3) Stats -->
    <section class="top-grid">

      <!-- 2) User information -->
      <div class="panel profile-card">
        <div class="profile-top">
          <img class="avatar" 
               src="../uploads/profiles/<?php echo htmlspecialchars($user['photoFileName']); ?>" 
               alt="Profile photo">
          <div>
            <div class="p-name"><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></div>
            <div class="p-email muted"><?php echo htmlspecialchars($user['emailAddress']); ?></div>
          </div>
        </div>
        <div class="profile-info">
          <div class="info">
            <span class="muted">First name</span>
            <span class="strong"><?php echo htmlspecialchars($user['firstName']); ?></span>
          </div>
          <div class="info">
            <span class="muted">Last name</span>
            <span class="strong"><?php echo htmlspecialchars($user['lastName']); ?></span>
          </div>
          <div class="info">
            <span class="muted">Email</span>
            <span class="strong"><?php echo htmlspecialchars($user['emailAddress']); ?></span>
          </div>
        </div>
      </div>

      <!-- 3) My recipes summary -->
      <div class="panel stats-card">
        <div class="stats-head">
          <h2>My Activity</h2>
          <span class="badge">This week</span>
        </div>
        <div class="stats-grid">
          <div class="stat">
            <div class="stat-num"><?php echo $recipeCount; ?></div>
            <div class="muted">Recipes shared</div>
          </div>
          <div class="stat">
            <div class="stat-num"><?php echo $likesCount; ?></div>
            <div class="muted">Total likes</div>
          </div>
        </div>
        <a class="link" href="../my_recipes-page/my-recipes.php">Go to My Recipes →</a>
      </div>

    </section>



    <!-- 5) Favourite recipes -->
    <section class="panel">
      <div class="section-head">
        <div>
          <h2>Favourites ❤️</h2>
          <p class="muted">Your favourite lunchbox ideas.</p>
        </div>
        <a class="btn btn-primary btn-explore" href="../explore-page/explore.php">
          Explore Lunchboxes <span class="arrow">→</span>
        </a>
      </div>

      <?php if ($favsResult && $favsResult->num_rows > 0): ?>
      <div class="fav-grid">
        <?php while ($fav = $favsResult->fetch_assoc()): ?>
        <div class="fav-card">
          <img class="fav-img" 
               src="../uploads/images/<?php echo htmlspecialchars($fav['photoFileName']); ?>" 
               alt="<?php echo htmlspecialchars($fav['name']); ?>">
          <div class="fav-body">
            <div class="fav-title">
              <a href="../view_recipe-page/view_recipe.php?id=<?php echo $fav['id']; ?>">
                <?php echo htmlspecialchars($fav['name']); ?>
              </a>
            </div>
            <div class="fav-actions">
              <a class="remove" href="../remove_favourite.php?recipeID=<?php echo $fav['id']; ?>">Remove</a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php else: ?>
        <p class="muted">You have no favourite recipes yet.</p>
      <?php endif; ?>

      <div class="signout-row">
        <a class="text-link" href="../logout.php">Sign out</a>
      </div>
    </section>









    <!-- 4) All Available Recipes -->
    <section class="panel">
      <div class="section-head">
        <div>
          <h2>All Available Recipes</h2>
          <p class="muted">Discover lunchbox ideas from the community.</p>
        </div>
        <!-- Filter form -->
        <form method="POST" action="user.php" class="filter-ui">
          <select name="category" class="select">
            <option value="all">All Categories</option>
            <?php while ($cat = $categoriesResult->fetch_assoc()): ?>
              <option value="<?php echo $cat['id']; ?>"
                <?php if (isset($_POST['category']) && $_POST['category'] == $cat['id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($cat['categoryName']); ?>
              </option>
            <?php endwhile; ?>
          </select>
          <button type="submit" class="btn-filter">🔍 Filter</button>
        </form>
      </div>

      <!-- Recipe cards -->
      <?php if ($recipesResult && $recipesResult->num_rows > 0): ?>
      <div class="recipe-grid">
        <?php while ($recipe = $recipesResult->fetch_assoc()): ?>
        <div class="recipe-card">
          <img class="r-img" 
               src="../uploads/images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>" 
               alt="<?php echo htmlspecialchars($recipe['name']); ?>">
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
            <p class="r-desc muted">
              <?php echo htmlspecialchars(substr($recipe['description'], 0, 60)) . '...'; ?>
            </p>
            <div class="r-creator">
              <img class="mini-ava" 
                   src="../uploads/images/<?php echo htmlspecialchars($recipe['userPhoto']); ?>" 
                   alt="creator">
              <span class="muted">
                <?php echo htmlspecialchars($recipe['firstName'] . ' ' . $recipe['lastName']); ?>
              </span>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php else: ?>
        <p class="muted" style="margin-top:1rem;">No recipes found in this category.</p>
      <?php endif; ?>
    </section>
















  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <span>&copy; 2026 Lunchy. All rights reserved.</span>
    </div>
  </footer>

</body>
</html>