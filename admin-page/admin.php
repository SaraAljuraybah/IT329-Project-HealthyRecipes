<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../db.php");

/* requirement 11-a */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login-page/login.html?error=Access denied");
    exit();
}

$adminID = intval($_SESSION['user_id']);

/* requirement 11-b */
$sqlAdmin = "SELECT * FROM user WHERE id = ? AND userType = 'admin'";
$stmtAdmin = mysqli_prepare($conn, $sqlAdmin);
mysqli_stmt_bind_param($stmtAdmin, "i", $adminID);
mysqli_stmt_execute($stmtAdmin);
$resultAdmin = mysqli_stmt_get_result($stmtAdmin);

if (!$resultAdmin || mysqli_num_rows($resultAdmin) == 0) {
    echo "Admin not found.";
    exit();
}

$admin = mysqli_fetch_assoc($resultAdmin);

/* requirement 11-c */
$sqlReports = "SELECT 
                    report.id AS reportID,
                    report.recipeID,
                    recipe.name AS recipeName,
                    recipe.userID AS recipeOwnerID,
                    user.firstName,
                    user.lastName,
                    user.emailAddress
               FROM report
               JOIN recipe ON report.recipeID = recipe.id
               JOIN user ON recipe.userID = user.id
               ORDER BY report.id DESC";
$resultReports = mysqli_query($conn, $sqlReports);
$reportsCount = ($resultReports) ? mysqli_num_rows($resultReports) : 0;

/* requirement 11-d */
$sqlBlocked = "SELECT * FROM blockeduser ORDER BY id DESC";
$resultBlocked = mysqli_query($conn, $sqlBlocked);
$blockedCount = ($resultBlocked) ? mysqli_num_rows($resultBlocked) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <link rel="stylesheet" href="../style.css">

<style>
.page{ padding: 22px 0 30px; }

.page-top{
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin: 12px 0 16px;
}

.page-title h1{
  margin: 8px 0 6px;
  font-size: 26px;
  letter-spacing: 0.2px;
}

.highlight{
  color: var(--primary-3);
  font-weight: 900;
}

.muted{ color: var(--muted); }

.panel{
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: 0 10px 24px rgba(22, 59, 47, 0.08);
  padding: 16px;
  margin: 12px 0;
}

.panel-head{
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.panel h2{
  margin: 0;
  font-size: 18px;
}

.count-pill{
  font-size: 12px;
  font-weight: 900;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255, 242, 194, 0.65);
  border: 1px solid rgba(255, 242, 194, 0.95);
}

.info-grid{
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.info-item{
  padding: 12px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: rgba(99,211,165,0.08);
}

.info-label{
  display: block;
  font-size: 12px;
  color: var(--muted);
  font-weight: 800;
  margin-bottom: 6px;
}

.info-value{
  font-weight: 900;
}

.table-wrap{
  overflow: auto;
  border: 1px solid var(--border);
  border-radius: 14px;
}

.table{
  width: 100%;
  border-collapse: collapse;
  min-width: 760px;
  background: #fff;
}

.table thead th{
  text-align: left;
  font-size: 12px;
  letter-spacing: 0.3px;
  text-transform: uppercase;
  padding: 12px 14px;
  color: var(--muted);
  background: rgba(99,211,165,0.10);
  border-bottom: 1px solid var(--border);
}

.table tbody td{
  padding: 14px;
  vertical-align: top;
  border-bottom: 1px solid var(--border);
}

.table tbody tr:hover{
  background: rgba(99,211,165,0.06);
}

.table-link{
  font-weight: 900;
  color: var(--primary-3);
}

.user-mini{
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar{
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: none;
}

.user-name{
  font-weight: 900;
}

.action-form{
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.select{
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: #fff;
  color: var(--text);
  font-weight: 800;
  min-width: 170px;
}

.btn-small{
  padding: 10px 12px;
  border-radius: 14px;
}

@media (max-width: 900px){
  .page-top{ flex-direction: column; }
  .info-grid{ grid-template-columns: 1fr; }
  .table{ min-width: 620px; }
}
</style>
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
        <a class="nav-link" href="../explore-page/explore.php">Explore</a>
        <a class="nav-link" href="../my_recipes-page/my-recipes.php">My Recipes</a>
        <a class="nav-link" href="../about-us-page/about-us.html">About Us</a>
      </nav>
      <div class="actions">
        <a class="btn btn-primary" href="admin.php">My Profile</a>
        <a class="btn btn-ghost" href="../logout.php">Log Out</a>
      </div>
    </div>
  </header>

<main class="container page">
  <section class="page-top">
    <div class="page-title">
      <h1>Welcome, <span class="highlight"><?php echo htmlspecialchars($admin['firstName']); ?></span> 👋</h1>
      <p class="muted">Review reported content and manage user access</p>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2>Admin Information</h2>
    </div>

    <div class="info-grid">
      <div class="info-item">
        <span class="info-label">Name</span>
        <span class="info-value"><?php echo htmlspecialchars($admin['firstName'] . " " . $admin['lastName']); ?></span>
      </div>

      <div class="info-item">
        <span class="info-label">Email</span>
        <span class="info-value"><?php echo htmlspecialchars($admin['emailAddress']); ?></span>
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2>Pending Reports</h2>
      <span class="count-pill"><?php echo $reportsCount; ?> reports</span>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Recipe</th>
            <th>Recipe Creator</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
        <?php if ($resultReports && mysqli_num_rows($resultReports) > 0) { ?>
            <?php while ($report = mysqli_fetch_assoc($resultReports)) { ?>
              <tr>
                <td>
                  <a class="table-link" href="../view_recipe-page/view_recipe.php?id=<?php echo $report['recipeID']; ?>">
                    <?php echo htmlspecialchars($report['recipeName']); ?>
                  </a>
                </td>

                <td>
                  <div class="user-mini">
                    <img class="avatar" src="../media/user.png" alt="user">
                    <div>
                      <div class="user-name"><?php echo htmlspecialchars($report['firstName'] . " " . $report['lastName']); ?></div>
                      <div class="muted"><?php echo htmlspecialchars($report['emailAddress']); ?></div>
                    </div>
                  </div>
                </td>

                <td>
                  <form class="action-form" action="handle_report.php" method="post">
                    <input type="hidden" name="reportID" value="<?php echo $report['reportID']; ?>">
                    <input type="hidden" name="recipeID" value="<?php echo $report['recipeID']; ?>">

                    <select class="select" name="action" required>
                      <option value="" selected disabled>Choose action</option>
                      <option value="block">Block user</option>
                      <option value="dismiss">Dismiss report</option>
                    </select>

                    <button class="btn btn-primary btn-small" type="submit">Submit</button>
                  </form>
                </td>
              </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
              <td colspan="3">No reports found.</td>
            </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2>Blocked Users</h2>
      <span class="count-pill"><?php echo $blockedCount; ?> users</span>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
          </tr>
        </thead>

        <tbody>
        <?php if ($resultBlocked && mysqli_num_rows($resultBlocked) > 0) { ?>
            <?php while ($blocked = mysqli_fetch_assoc($resultBlocked)) { ?>
              <tr>
                <td>
                  <div class="user-mini">
                    <img class="avatar" src="../media/user.png" alt="user">
                    <div class="user-name"><?php echo htmlspecialchars($blocked['firstName'] . " " . $blocked['lastName']); ?></div>
                  </div>
                </td>
                <td class="muted"><?php echo htmlspecialchars($blocked['emailAddress']); ?></td>
              </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
              <td colspan="2">No blocked users.</td>
            </tr>
        <?php } ?>
        </tbody>
      </table>
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