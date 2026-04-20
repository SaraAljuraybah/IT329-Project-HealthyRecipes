<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login-page/login.html");
    exit();
}

include("../db.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Recipe not found.";
    exit();
}

$recipeID = intval($_GET['id']);

$currentUserID = $_SESSION['user_id'];
$currentUserType = $_SESSION['user_type'];

$sql = "SELECT recipe.*, user.firstName, user.lastName, user.photoFileName AS userPhoto, recipecategory.categoryName
        FROM recipe
        JOIN user ON recipe.userID = user.id
        JOIN recipecategory ON recipe.categoryID = recipecategory.id
        WHERE recipe.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $recipeID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo "Recipe not found.";
    exit();
}

$recipe = mysqli_fetch_assoc($result);

$sqlIngredients = "SELECT * FROM ingredients WHERE recipeID = ?";
$stmtIngredients = mysqli_prepare($conn, $sqlIngredients);
mysqli_stmt_bind_param($stmtIngredients, "i", $recipeID);
mysqli_stmt_execute($stmtIngredients);
$resultIngredients = mysqli_stmt_get_result($stmtIngredients);

$sqlInstructions = "SELECT * FROM instructions WHERE recipeID = ? ORDER BY stepOrder ASC";
$stmtInstructions = mysqli_prepare($conn, $sqlInstructions);
mysqli_stmt_bind_param($stmtInstructions, "i", $recipeID);
mysqli_stmt_execute($stmtInstructions);
$resultInstructions = mysqli_stmt_get_result($stmtInstructions);

$sqlComments = "SELECT comment.*, user.firstName, user.lastName, user.photoFileName
                FROM comment
                JOIN user ON comment.userID = user.id
                WHERE comment.recipeID = ?
                ORDER BY comment.date DESC";
$stmtComments = mysqli_prepare($conn, $sqlComments);
mysqli_stmt_bind_param($stmtComments, "i", $recipeID);
mysqli_stmt_execute($stmtComments);
$resultComments = mysqli_stmt_get_result($stmtComments);

$liked = false;
$sqlLike = "SELECT * FROM likes WHERE userID = ? AND recipeID = ?";
$stmtLike = mysqli_prepare($conn, $sqlLike);
mysqli_stmt_bind_param($stmtLike, "ii", $currentUserID, $recipeID);
mysqli_stmt_execute($stmtLike);
$resultLike = mysqli_stmt_get_result($stmtLike);
if (mysqli_num_rows($resultLike) > 0) {
    $liked = true;
}

$favourited = false;
$sqlFav = "SELECT * FROM favourites WHERE userID = ? AND recipeID = ?";
$stmtFav = mysqli_prepare($conn, $sqlFav);
mysqli_stmt_bind_param($stmtFav, "ii", $currentUserID, $recipeID);
mysqli_stmt_execute($stmtFav);
$resultFav = mysqli_stmt_get_result($stmtFav);
if (mysqli_num_rows($resultFav) > 0) {
    $favourited = true;
}

$reported = false;
$sqlReport = "SELECT * FROM report WHERE userID = ? AND recipeID = ?";
$stmtReport = mysqli_prepare($conn, $sqlReport);
mysqli_stmt_bind_param($stmtReport, "ii", $currentUserID, $recipeID);
mysqli_stmt_execute($stmtReport);
$resultReport = mysqli_stmt_get_result($stmtReport);
if (mysqli_num_rows($resultReport) > 0) {
    $reported = true;
}

$sqlCountLikes = "SELECT COUNT(*) AS totalLikes FROM likes WHERE recipeID = ?";
$stmtCountLikes = mysqli_prepare($conn, $sqlCountLikes);
mysqli_stmt_bind_param($stmtCountLikes, "i", $recipeID);
mysqli_stmt_execute($stmtCountLikes);
$resultCountLikes = mysqli_stmt_get_result($stmtCountLikes);
$rowLikes = mysqli_fetch_assoc($resultCountLikes);
$totalLikes = $rowLikes['totalLikes'];

$showButtons = true;
if ($currentUserID == $recipe['userID'] || $currentUserType == 'admin') {
    $showButtons = false;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View recipe page</title>
    <link rel="stylesheet" href="style-vr.css">
    <script src="Script.js" defer></script>
</head>
<body class="body_vr">

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
        <a class="nav-link" href="../explore-page/explore.php">Explore</a>
        <a class="nav-link" href="../my_recipes-page/my-recipes.php">My Recipes</a>
        <a class="nav-link" href="../about-us-page/about-us.html">About Us</a>
      </nav>
      <div class="actions">
        <a class="btn btn-primary" href="../user-page/user.php">My Profile</a>
        <a class="btn btn-ghost" href="../logout.php">Log Out</a>
      </div>
    </div>
  </header>

<?php if ($showButtons) { ?>
<div class="actions_vr">
    <form action="add_favourite.php" method="post">
        <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
        <button type="submit" class="btn_vr btn-square_vr <?php echo $favourited ? 'btn-done_vr' : 'btn-gradient_vr'; ?>" <?php if ($favourited) echo "disabled"; ?>>
            <img src="../media/fav-icon.png" alt="" class="btn-icon_vr">
            <span><?php echo $favourited ? "Added" : "Favourites"; ?></span>
        </button>
    </form>

    <form action="add_like.php" method="post">
        <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
        <button type="submit" class="btn_vr btn-square_vr <?php echo $liked ? 'btn-done_vr' : 'btn-gradient_vr'; ?>" <?php if ($liked) echo "disabled"; ?>>
            <img src="../media/like-icon.png" alt="" class="btn-icon_vr">
            <span><?php echo $liked ? "Liked" : "Like"; ?></span>
        </button>
    </form>

    <form action="add_report.php" method="post">
        <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
        <button type="submit" class="btn_vr btn-square_vr <?php echo $reported ? 'btn-reported_vr' : 'btn-report_vr'; ?>" <?php if ($reported) echo "disabled"; ?>>
            <img src="../media/report-icon.png" alt="" class="btn-icon_vr">
            <span><?php echo $reported ? "Reported" : "Report"; ?></span>
        </button>
    </form>
</div>
<?php } ?>

<main class="container container_vr">

    <section class="hero_vr">
        <div class="card_vr">
            <h1 class="title_vr"><?php echo htmlspecialchars($recipe['name']); ?></h1>

            <div class="image-container_vr">
                <img src="../uploads/images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>" alt="Recipe Image" class="recipe-img_vr">
            </div>

            <div class="catchy-text_vr">
                <p><?php echo htmlspecialchars($recipe['description']); ?></p>
            </div>
        </div>
    </section>

    <section class="grid_vr">
        <article class="card_vr">
            <div class="card-top_vr"><h2>Recipe Creator</h2></div>
            <div class="creator-box_vr">
  <?php 
    $userImg = $recipe['userPhoto']; // غيرنا photoFileName إلى userPhoto
    $folder = ($userImg == "default-user.png" || empty($userImg)) ? "images" : "profiles";
?>
<img src="../uploads/<?php echo $folder; ?>/<?php echo htmlspecialchars($userImg); ?>" alt="Profile Icon" class="profile-icon_vr">  <span class="user-name_vr">
        <?php echo htmlspecialchars($recipe['firstName'] . " " . $recipe['lastName']); ?>
    </span>
</div>
        </article>

        <article class="card_vr">
            <div class="card-top_vr"><h2>Details</h2></div>
            <p><span class="tag_vr tag-protein_vr"><?php echo htmlspecialchars($recipe['categoryName']); ?></span></p>
            <p class="desc_text_vr"><strong>Lunch Box Type:</strong> <?php echo htmlspecialchars($recipe['lunchBoxType']); ?></p>
            <p class="desc_text_vr"><strong>Total Likes:</strong> <?php echo $totalLikes; ?></p>
        </article>
    </section>

    <section class="grid_vr">
        <article class="card_vr">
            <div class="card-top_vr">
                <h2>Ingredients Progress</h2>
                <span id="progress_text_vr" class="badge">0%</span>
            </div>

            <div class="progress_container_vr">
                <div id="progress_bar_vr" class="progress_bar_vr"></div>
            </div>

            <ul class="list_vr ingredients_list_vr">
                <?php while ($ingredient = mysqli_fetch_assoc($resultIngredients)) { ?>
                    <li>
                        <label class="checkbox_container_vr">
                            <input type="checkbox" class="ingredient_check_vr">
                            <?php echo htmlspecialchars($ingredient['ingredientName']); ?>
                            <span class="grams_vr">(<?php echo htmlspecialchars($ingredient['ingredientQuantity']); ?>)</span>
                            <span class="checkmark_vr"></span>
                        </label>
                    </li>
                <?php } ?>
            </ul>
        </article>

        <article class="card_vr">
            <div class="card-top_vr"><h2>Instructions</h2></div>
            <ol class="list_vr">
                <?php while ($instruction = mysqli_fetch_assoc($resultInstructions)) { ?>
                    <li><?php echo htmlspecialchars($instruction['step']); ?></li>
                <?php } ?>
            </ol>
        </article>
    </section>

    <section class="card_vr video-card_vr">
        <div class="card-top_vr"><h2>Recipe Video</h2></div>
        <div class="video-content_vr">
            <?php if (!empty($recipe['videoFilePath'])) { ?>
                <video controls class="recipe-video_vr">
                    <source src="../media/<?php echo htmlspecialchars($recipe['videoFilePath']); ?>" type="video/mp4">
                </video>
            <?php } elseif (!empty($recipe['videoURL'])) { ?>
                <a href="<?php echo htmlspecialchars($recipe['videoURL']); ?>" target="_blank" class="link_vr">Watch Video 🔗</a>
            <?php } else { ?>
                <p>No video available.</p>
            <?php } ?>
        </div>
    </section>

    <section class="card_vr">
        <div class="card-top_vr"><h2>Comments</h2></div>

        <form action="add_comment.php" method="post" class="comment-form_vr">
            <input type="hidden" name="recipeID" value="<?php echo $recipeID; ?>">
            <textarea name="comment" class="input_vr" placeholder="Add a comment..." required></textarea>
            <button type="submit" class="btn_vr btn-primary_vr">Add Comment</button>
        </form>

        <div class="comments-scroll-box_vr">
            <?php if (mysqli_num_rows($resultComments) > 0) { ?>
                <?php while ($comment = mysqli_fetch_assoc($resultComments)) { 
            
                  $cImg = $comment['photoFileName'];
                    $cFolder = ($cImg == "default-user.png") ? "images" : "profiles";
                ?>

                


                    <div class="comment-item_vr">
                        <div class="comment-text-wrapper_vr">
                            <div class="comment-header_vr">
                                <span class="comment-author_vr"><?php echo htmlspecialchars($comment['firstName'] . " " . $comment['lastName']); ?></span>
                                <span class="comment-date_vr"><?php echo date("Y-m-d h:i A", strtotime($comment['date'])); ?></span>
                            </div>
                            <p class="comment-body_vr"><?php echo htmlspecialchars($comment['comment']); ?></p>
                        </div>
                        <img src="../uploads/<?php echo $cFolder; ?>/<?php echo htmlspecialchars($cImg); ?>" alt="Profile" class="profile-icon_vr">
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p>No comments yet.</p>
            <?php } ?>
        </div>
    </section>

</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>&copy; 2026 Lunchy. All rights reserved.</span>
    </div>
</footer>

</body>
</html>